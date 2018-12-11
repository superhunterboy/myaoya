<?php

namespace Weiming\Controllers;

use Illuminate\Pagination\Paginator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\AgencyPayments\Aifu;
use Weiming\Libs\AgencyPayments\Bingo;
use Weiming\Libs\AgencyPayments\Chuanhua;
use Weiming\Libs\AgencyPayments\Duobao;
use Weiming\Libs\AgencyPayments\Jiayoutong;
use Weiming\Libs\AgencyPayments\Jinhaizhe;
use Weiming\Libs\AgencyPayments\KaiLianTong;
use Weiming\Libs\AgencyPayments\Nongfu;
use Weiming\Libs\AgencyPayments\Shangma;
use Weiming\Libs\AgencyPayments\Shunxin;
use Weiming\Libs\AgencyPayments\Tianfubao;
use Weiming\Libs\AgencyPayments\Xifu;
use Weiming\Libs\AgencyPayments\Xunjie;
use Weiming\Libs\AgencyPayments\Yafu;
use Weiming\Libs\AgencyPayments\Yibao;
use Weiming\Libs\AgencyPayments\Zesheng;
use Weiming\Libs\AgencyPayments\Zhongdian;
use Weiming\Libs\AgencyPayments\Xinxinju;
use Weiming\Libs\AgencyPayments\Gaiya;
use Weiming\Libs\AgencyPayments\Qingying;
use Weiming\Libs\AgencyPayments\Jiyun;
use Weiming\Libs\Utils;
use Weiming\Models\Member;
use Weiming\Models\Platform;
use Weiming\Models\Withdrawal;

class WithdrawalsController extends BaseController
{
    public function withdrawals(Request $request, Response $response, $args)
    {
        $result       = ['status' => 1, 'msg' => '参数错误', 'data' => []];
        $postDatas    = $request->getParsedBody();
        $platformType = $postDatas['platformType'] ?? 0;
        $mchtId       = $postDatas['mchtId'] ?? '';
        $account      = $postDatas['account'] ?? '';
        $username     = $postDatas['username'] ?? '';
        $bankNo       = $postDatas['bankNo'] ?? '';
        $bankName     = $postDatas['bankName'] ?? '';
        $amount       = $postDatas['amount'] ?? 0;
        //$mobile       = $postDatas['mobile'] ?? '';
        $mobile       = '';
        $province     = $postDatas['province'] ?? '';
        $city         = $postDatas['city'] ?? '';
        $branch       = $postDatas['branch'] ?? '';
        $subbranch    = $postDatas['subbranch'] ?? '';
        // 手输入银行名称
        if ($bankName == 'enterBankName') {
            $bankName = $postDatas['enterBankName'] ?? '';
        }
        if ($platformType > 0 && $mchtId && $username && $bankNo && $amount > 0) {
            // 会员验证，account 有值就验证，反之不验证
            $memberId = 0; // 不是会员
            if ($account) {
                $account = trim($account);
                $member  = Member::where('account', '=', $account)->first();
                if (empty($member)) {
                    $result['msg'] = '会员账号不存在';
                    return $response->withJson($result);
                } else {
                    $memberId = $member->id;
                }
            }
            if (in_array($platformType, [1, 3, 7]) && empty($mobile)) {
                return $response->withJson($result);
            }
            if (in_array($platformType, [2]) && (empty($province) || empty($city))) {
                return $response->withJson($result);
            }
            if (!in_array($platformType, [7, 14]) && empty($bankName)) {
                return $response->withJson($result);
            }
            // if (in_array($platformType, []) && empty($branch)) {
            //     return $response->withJson($result);
            // }
            // yafu、shangma、aifu、nongfu、shunxin、bingo要传银行编码，扯淡滴很，fuck
            $bankCode = 'UNKNOWN';
            if (in_array($platformType, [2, 8, 10, 11, 12, 16, 17, 20])) {
                $bankLists = [];
                if ($platformType == 2) {
                    $bankLists = $this->settings['bank'];
                } elseif ($platformType == 8) {
                    $bankLists = $this->settings['shangma'];
                } elseif ($platformType == 10) {
                    $bankLists = $this->settings['aifu'];
                } elseif ($platformType == 11) {
                    $bankLists = $this->settings['nongfu'];
                } elseif ($platformType == 12) {
                    $bankLists = $this->settings['shunxin'];
                } elseif ($platformType == 16) {
                    $bankLists = $this->settings['bingo'];
                } elseif ($platformType == 17) {
                    $bankLists = $this->settings['yibao'];
                } elseif ($platformType == 20) {
                    $bankLists = $this->settings['gaiya'];
                }
                foreach ($bankLists as $bankNameKeyword => $bankCode) {
                    if (strpos($bankName, $bankNameKeyword) !== false) {
                        $bankCode = $bankCode;
                        break;
                    }
                }
            }

            $orderId  = Utils::getOrderId(date('YmdHis'));
            $platform = Platform::where('no', '=', $mchtId)->where('pay_out_type', '=', $platformType)->where('type', '=', 1)->first();
            if ($platform) {
                // 防止重复出款
                $bankNo     = trim($bankNo);
                $username   = trim($username);
                $amount     = trim($amount);
                $uniqueMark = md5($bankNo . $username . $amount);
                $payOutLock = 'payOutLock:' . $uniqueMark;
                $isLock     = $this->redisLock->lock($payOutLock, 70);
                if ($isLock) {
                    $withdrawals = Withdrawal::create([
                        'platform_id' => $platform->id,
                        'order_no'    => $orderId,
                        'bank_no'     => $bankNo,
                        'bank_name'   => $bankName,
                        'username'    => $username,
                        'amount'      => $amount,
                        'mobile'      => $mobile,
                        'province'    => $province,
                        'city'        => $city,
                        'branch'      => $branch,
                        'status'      => 0,
                        'user_id'     => $this->jwt->userInfo->id,
                        'member_id'   => $memberId,
                        'account'     => $account,
                    ]);
                    if ($withdrawals) {
                        $res  = '';
                        $data = '';
                        $conf = [
                            'parterNo'  => $platform->no,
                            'parterKey' => $platform->key,
                        ];
                        if ($platformType == 1) {
                            $res = Tianfubao::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 2) {
                            $res = Yafu::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankCode, // 特殊处理
                                'tranAmt'  => $amount * 100, // 分
                                'province' => $province,
                                'city'     => $city,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 3) {
                            $res = Jinhaizhe::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 4) {
                            $res = Zesheng::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 5) {
                            $res = Chuanhua::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 6) {
                            $res = KaiLianTong::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                            ], 'payment')->sendRequest();
                            $data = $res['response']['envelope']['body'] ?? '';
                        } elseif ($platformType == 7) {
                            $res = Zhongdian::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 8) {
                            $res = Shangma::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                // 'province' => $province,
                                // 'city'     => $city,
                                'branch'   => $branch,
                                'bankCode' => $bankCode, // 特殊处理
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 9) {
                            $res = Xifu::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                // 'province'  => $province,
                                // 'city'      => $city,
                                // 'branch'    => $branch, // 分行
                                // 'subbranch' => $subbranch, // 支行
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 10) {
                            $res = Aifu::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                'branch'   => $branch, // 支行
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 11) {
                            $res = Nongfu::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                'bankCode' => $bankCode, // 特殊处理
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 12) {
                            $res = Shunxin::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'bankName' => $bankName,
                                'tranAmt'  => $amount * 100, // 分
                                'bankCode' => $bankCode, // 特殊处理
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 14) {
                            $channel = (isset($postDatas['channel']) && in_array($postDatas['channel'], ['8001', '8002'])) ? $postDatas['channel'] : '8002';
                            $res     = Xunjie::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'channel'  => $channel,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 15) {
                            $res = Duobao::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'bankName' => $bankName,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 16) {
                            $amount = $amount * 100; // 分
                            // 变种代付，需要先调用余额查询接口，来确定accountCode和accountType字段
                            list($accountCode, $accountType) = Utils::getBingoCanUseBalance($conf, $amount);
                            $res                             = Bingo::getInstance($conf)->generateSignature([
                                'orderNo'     => $orderId,
                                'acctId'      => $bankNo,
                                'acctName'    => $username,
                                'tranAmt'     => $amount,
                                'bankName'    => $bankName,
                                'bankCode'    => $bankCode,
                                'accountCode' => $accountCode,
                                'accountType' => $accountType,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 17) {
                            $res = Yibao::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'bankName' => $bankName,
                                'bankCode' => $bankCode,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        } elseif ($platformType == 18) {
                            $res = Xinxinju::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'bankName' => $bankName,
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        }elseif ($platformType == 19) {
                            $res = Jiayoutong::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount,
                                'bankName' => $bankName,
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        }elseif ($platformType == 20) {
                            $res = Gaiya::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'bankName' => $bankName,
                                'mobile'   => $mobile,
                                'bankCode' => $bankCode
                            ], 'payment')->sendRequest();
                            $data = $res;
                        }elseif ($platformType == 21) {
                            $res = Qingying::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'bankName' => $bankName,
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        }elseif ($platformType == 22) {
                            $res = Jiyun::getInstance($conf)->generateSignature([
                                'orderNo'  => $orderId,
                                'acctId'   => $bankNo,
                                'acctName' => $username,
                                'tranAmt'  => $amount * 100, // 分
                                'bankName' => $bankName,
                                'mobile'   => $mobile,
                            ], 'payment')->sendRequest();
                            $data = $res;
                        }
                    // 解析返回数据，更新状态、单号、返回消息
                        $parseResult                    = $this->parseWithdrawalStatus($data, $platformType, $conf, $orderId);
                        $withdrawals->platform_order_no = $parseResult['order_no'];
                        $withdrawals->status            = $parseResult['status']; // 状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
                        $withdrawals->note              = $parseResult['msg'];
                        $withdrawals->remark            = json_encode($res);
                        $withdrawals->save();
                        $result['status'] = 0;
                        $result['msg']    = '';
                        $result['data']   = $data;
                        // Redis 解锁
                        // $this->redisLock->unlock($payOutLock);
                    }
                } else {
                    $result['msg'] = '疑似70秒内重复出款';
                }
            } else {
                $result['msg'] = '代付通道错误';
            }
        }
        return $response->withJson($result);
    }

    private function parseWithdrawalStatus($res, $type, $conf, $no)
    {
        $tmpArr = [];

        if ($type == 1) {
            // 天付宝

            $tfb_serialno   = $res['tfb_serialno'] ?? '';
            $serialno_state = $res['serialno_state'] ?? 0;
            $serialno_desc  = $res['serialno_desc'] ?? '';

            if (empty($tfb_serialno) && $serialno_state == 2) {
                $payOutStatus   = Tianfubao::getInstance($conf)->generateSignature(['orderNo' => $no, 'tfbSerialNo' => $tfb_serialno], 'query')->sendRequest();
                $tfb_serialno   = $payOutStatus['tfb_serialno'] ?? '';
                $serialno_state = $payOutStatus['serialno_state'] ?? 2;
                $serialno_desc  = $payOutStatus['serialno_desc'] ?? '';
            }

            if ($serialno_state == 0) {
                $serialno_state = 0;
            } elseif ($serialno_state == 1) {
                $serialno_state = 1;
            } elseif ($serialno_state == 2) {
                $serialno_state = 2;
            } elseif ($serialno_state == 3) {
                $serialno_state = 3;
            } elseif ($serialno_state == 4) {
                $serialno_state = 4;
            } else {
                $serialno_state = 5;
            }

            $tmpArr['order_no'] = $tfb_serialno;
            $tmpArr['status']   = $serialno_state;
            $tmpArr['msg']      = $serialno_desc;
        } elseif ($type == 2) {
            // 雅付

            $orderNo = $res['orderNo'] ?? '';
            $code    = $res['code'] ?? 0;
            $msg     = $res['msg'] ?? '';

            if (empty($orderNo) && $code != '000000') {
                // 处理中，反查一次代付结果
                $tmpStatus = [
                    0 => '未处理',
                    1 => '成功',
                    2 => '已取消',
                    3 => '提现失败',
                    4 => '提现处理中',
                    5 => '部分成功',
                ];
                $payOutStatus = Yafu::getInstance($conf)->generateSignature(['merOrderNo' => $no], 'query')->sendRequest();
                $orderNo      = $payOutStatus['casOrdNo'] ?? '';
                $code         = $payOutStatus['orderStatus'] ?? 4;
                $msg          = $tmpStatus[$code] ?? '未知';
            }

            if ($code == 0) {
                $code = 0;
            } elseif ($code == 1) {
                $code = 1;
            } elseif ($code == 2) {
                $code = 4;
            } elseif ($code == 3) {
                $code = 3;
            } elseif ($code == 4) {
                $code = 2;
            } else {
                $code = 5;
            }

            $tmpArr['order_no'] = $orderNo;
            $tmpArr['status']   = $code;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 3) {
            // 金海哲

            $sy_request_no = $res['sy_request_no'] ?? '';
            $status        = $res['code'] ?? ($res['status'] ?? 1);
            $msg           = $res['msg'] ?? ($res['origData']['retMsg'] ?? '');

            if ($status == 0) {
                $status = 0;
            } elseif ($status == 1) {
                $status = 2;
            } elseif ($status == 2) {
                $status = 1;
            } elseif ($status == 3) {
                $status = 3;
            } elseif ($status == 4) {
                $status = 4;
            } else {
                $status = 5;
            }

            $tmpArr['order_no'] = $sy_request_no;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 4) {
            // 泽圣

            $orderId = $res['orderId'] ?? '';
            $status  = $res['state'] ?? '01';
            $msg     = $res['errorMsg'] ?? ($res['origData']['msg'] ?? '');

            if (!empty($orderId) && $status == '01') {
                // 处理中，反查一次代付结果
                $payOutStatus = Zesheng::getInstance($conf)->generateSignature(['orderNo' => $no], 'query')->sendRequest();
                $orderId      = $payOutStatus['orderId'] ?? '';
                $status       = $payOutStatus['state'] ?? '01';
                $msg          = $payOutStatus['errorMsg'] ?? ($payOutStatus['origData']['msg'] ?? '未知');
            }

            if ($status == '00') {
                $status = 1;
            } elseif ($status == '01' || $status == '90') {
                $status = 2;
            } elseif ($status == '02') {
                $status = 3;
            } else {
                $status = 5;
            }

            $tmpArr['order_no'] = $orderId;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 5) {
            // 传化

            $code    = $res['code'] ?? '';
            $status  = $res['result'] ?? '';
            $msg     = $res['msg'] ?? '';
            $orderId = '';
            $state   = '';

            if ($status == 'success') {
                $payOutStatus = Chuanhua::getInstance($conf)->generateSignature(['orderNo' => $no], 'query')->sendRequest();
                $status       = $payOutStatus['result'] ?? '';
                $msg          = $payOutStatus['msg'] ?? '';
                $state        = $payOutStatus['data']['status'] ?? '';
                $orderId      = $payOutStatus['data']['businessrecordnumber'] ?? '';

                if (strpos($state, '成功') !== false) {
                    $status = 1;
                } elseif (strpos($state, '处理中') !== false) {
                    $status = 2;
                } elseif (strpos($state, '失败') !== false) {
                    $status = 3;
                } elseif (strpos($state, '已退票') !== false) {
                    $status = 4;
                } else {
                    $status = 5;
                }
            } else {
                $status = 5;
                $state  = $msg;
            }

            $tmpArr['order_no'] = $orderId;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $state;
        } elseif ($type == 6) {
            // 开联通

            $code   = $res['response']['envelope']['body']['responseCode'] ?? '';
            $status = $res['response']['envelope']['body']['status'] ?? ''; // 非法交易 INVALID、已受理 CREATED、交易中 TX_BEGIN、交易成功 TX_SUCCESS、交易失败 TX_FAIL、关闭 CLOSED
            $msg    = $res['response']['envelope']['body']['responseMsg'] ?? '';

            if ($code == 'E0000') {
                // 已受理、交易中
                if ($status == 'CREATED' || $status == 'TX_BEGIN') {
                    $payOutStatus = KaiLianTong::getInstance($conf)->generateSignature(['orderNo' => $no], 'query')->sendRequest();
                    $code         = $payOutStatus['response']['envelope']['body']['responseCode'] ?? '';
                    $status       = $payOutStatus['response']['envelope']['body']['status'] ?? '';
                    $msg          = $payOutStatus['response']['envelope']['body']['responseMsg'] ?? '';
                }

                // 统一状态，非法交易 INVALID、已受理 CREATED、交易中 TX_BEGIN、交易成功 TX_SUCCESS、交易失败 TX_FAIL、关闭 CLOSED
                if ($status == 'TX_SUCCESS') {
                    $status = 1;
                } elseif ($status == 'CREATED' || $status == 'TX_BEGIN') {
                    $status = 2;
                } elseif ($status == 'TX_FAIL') {
                    $status = 3;
                } elseif ($status == 'INVALID') {
                    $status = 5;
                }
                // PS: 第三方说关闭CLOSED状态不用管
            } else {
                $status = 5;
            }

            $tmpArr['order_no'] = 'NO_ORDER_NUMBER';
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 7) {
            // 众点

            $code    = $res['result_code'] ?? '';
            $status  = $res['result_code'] ?? ''; // 0 成功、非0失败
            $msg     = $res['result_msg'] ?? '';
            $orderId = $res['transaction_id'] ?? '';

            if ($status == 0) {
                $status = 1;
            } else {
                $status = 5;
            }

            $tmpArr['order_no'] = $orderId;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 8) {
            // 商码付

            $resultCode = $res['resultCode'] ?? '01';
            $resultMsg  = $res['resultMsg'] ?? '';
            $orderNo    = $res['outTradeNo'] ?? '';
            $returnCode = $res['returnCode'] ?? -1;

            if ($returnCode == 0) {
                $returnCode = 0;
            } elseif ($returnCode == 1) {
                $returnCode = 2;
            } elseif ($returnCode == 2) {
                $returnCode = 1;
            } elseif ($returnCode == -1) {
                $returnCode = 3;
            } elseif ($returnCode == -2) {
                $returnCode = 5;
            }

            $tmpArr['order_no'] = $orderNo;
            $tmpArr['status']   = $returnCode;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 9) {
            // 喜付

            $resultCode = $res['respCode'] ?? 'F9999';
            $resultMsg  = $res['respMessage'] ?? '';

            if ($resultCode == 'S0001') {
                $resultCode = 2;
            } else {
                $resultCode = 3;
            }

            $tmpArr['order_no'] = 'NO_ORDER_NUMBER';
            $tmpArr['status']   = $resultCode;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 10) {
            // 艾付

            $resultCode = $res['result_code'] ?? '';
            $resultMsg  = $res['result_msg'] ?? '';
            $resultFlag = $res['result'] ?? 'H';
            $orderNo    = $res['order_no'] ?? '';

            // 统一状态
            if ($resultCode == '000000' || $resultCode == 'TRS003') {
                if ($resultFlag == 'H') {
                    $resultCode = 2;
                } elseif ($resultFlag == 'F') {
                    $resultCode = 3;
                } elseif ($resultFlag == 'S') {
                    $resultCode = 1;
                } else {
                    $resultCode = 5;
                }
            } else {
                $resultCode = 3;
            }

            $tmpArr['order_no'] = $orderNo;
            $tmpArr['status']   = $resultCode;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 11) {
            // Nong付

            $resultCode = $res['code'] ?? '1002';
            $resultMsg  = $res['msg'] ?? '';
            $resultFlag = $res['success'] ?? 0;

            if ($resultFlag == 1) {
                if ($resultCode == '1000') {
                    $resultCode = 0;
                } elseif ($resultCode == '1002') {
                    // $resultCode = 3;
                    $resultCode = 2; // fuck, Npay那边下单成功或者失败，过一会儿会失败或者成功
                } elseif ($resultCode == '1001') {
                    // $resultCode = 1;
                    $resultCode = 2; // fuck, Npay那边下单成功或者失败，过一会儿会失败或者成功
                } elseif ($resultCode == '1111') {
                    $resultCode = 2;
                } else {
                    $resultCode = 5;
                }
            } else {
                $resultCode = 3;
            }

            $tmpArr['order_no'] = 'NO_ORDER_NUMBER';
            $tmpArr['status']   = $resultCode;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 12) {
            // 顺心

            $resultMsg  = $res['respDesc'] ?? '';
            $resultFlag = $res['respCode'] ?? '05';
            $orderNo    = $res['batchNo'] ?? '';

            $resultCode = 3;
            if ($resultFlag == '00') {
                $resultCode = 2;
            }

            $tmpArr['order_no'] = $orderNo;
            $tmpArr['status']   = $resultCode;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 14) {
            // 迅捷

            $resultMsg  = $res['respDesc'] ?? '';
            $resultFlag = $res['respCode'] ?? '';
            $orderNo    = $res['serialId'] ?? '';

            $resultCode = 3;
            if ($resultFlag == '0000') {
                $resultCode = 1;
            } elseif ($resultFlag == 'P000' || $resultFlag == 'P999') {
                $resultCode = 2;
            } elseif ($resultFlag == '9996') {
                $resultCode = 4;
            } elseif ($resultFlag == '9998') {
                $resultCode = 3;
            } elseif ($resultFlag == '9999') {
                $resultCode = 5;
            }

            $tmpArr['order_no'] = $orderNo;
            $tmpArr['status']   = $resultCode;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 15) {
            // 多宝

            $resultCode = $res['status'] ?? 'error'; // success
            $resultMsg  = $res['msg'] ?? '';
            $settleid   = $res['settleid'] ?? '';

            $status = 0;
            if ($resultCode == 'success') {
                $status = 2;
            } elseif ($resultCode == 'error') {
                $status = 3;
            }

            $tmpArr['order_no'] = $settleid;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $resultMsg;
        } elseif ($type == 16) {
            // Bingo

            $respCode = $res['respCode'] ?? '';
            $code     = $res['key'] ?? '';
            $msg      = $res['msg'] ?? '';

            $status = 3;
            if ($respCode == '00' && $code == '00') {
                $status = 1;
            } elseif ($respCode == '00' && $code == '05') {
                $status = 2;
            }

            $tmpArr['order_no'] = 'NO_ORDER_NUMBER';
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 17) {
            // Yibao

            $ret_Code = $res['ret_Code'] ?? '';
            $r1_Code  = $res['r1_Code'] ?? '';
            $msg      = $res['error_Msg'] ?? '';

            $status = 3;
            if ($ret_Code == 1) {
                $status = 2;
                if ($r1_Code == '0026') {
                    $status = 1;
                } elseif ($r1_Code == '0027') {
                    $status = 4;
                } elseif ($r1_Code == '0028') {
                    $status = 3;
                }
            }

            $tmpArr['order_no'] = 'NO_ORDER_NUMBER';
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 18) {
            // Xinxinju
            $ret_Code           = $res['field039'] ?? '';
            $platform_order_no  = $res['field062'] ?? '';
            $msg                = $res['field124'] ?? '';

            // 统一状态
            $status = 2;
            if ($ret_Code == '00') {
                $status = 2;
            } elseif ($ret_Code == '01') {
                $status = 2;
            } elseif ($ret_Code == '02') {
                $status = 2;
            } elseif ($ret_Code == '03') {
                $status = 3;
            }

            $tmpArr['order_no'] = $platform_order_no;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 19) {
            // Jiayoutong
            $ret_Code           = $res['code'] ?? '';
            $resStatus          = $res['status'] ?? '';
            $platform_order_no  = $res['businessNo'] ?? '';
            $msg                = $res['describe'] ?? '';

            // 统一状态
            $status = 3;
            if ($ret_Code == '00') {
                if ($resStatus == 1) {
                    $status = 1;
                } elseif ($resStatus == 2) {
                    $status = 3;
                } elseif ($resStatus == 4) {
                    $status = 2;
                } elseif ($resStatus == 5) {
                    $status = 4;
                }
            }

            $tmpArr['order_no'] = $platform_order_no;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 20) {
            // Jiayoutong
            $ret_Code           = $res['success'] ?? '';
            $resStatus          = $res['code'] ?? '';
            $platform_order_no  = $res['merOrderId'] ?? '';
            $msg                = $res['msg'] ?? '';
            //状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            // 统一状态
            $status = 3;
            if ($ret_Code == 1) {
                if ($resStatus == '0000') {
                    $status = 0;
                } elseif ($resStatus == '1001') {
                    $status = 1;
                } elseif ($resStatus == '1111') {
                    $status = 2;
                } elseif ($resStatus == '1002') {
                    $status = 3;
                }
            }

            $tmpArr['order_no'] = $platform_order_no;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 21) {
            // 青英
            $ret_Code           = $res['field039'] ?? '';
            $platform_order_no  = $res['field062'] ?? '';
            $msg                = $res['field124'] ?? '';

            // 统一状态
            $status = 2;
            if ($ret_Code == '00') {
                $status = 2;
            } elseif ($ret_Code == '01') {
                $status = 2;
            } elseif ($ret_Code == '02') {
                $status = 2;
            } elseif ($ret_Code == '03') {
                $status = 3;
            }

            $tmpArr['order_no'] = $platform_order_no;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        } elseif ($type == 22) {
            // 极云
            $ret_Code           = $res['field039'] ?? '';
            $platform_order_no  = $res['field062'] ?? '';
            $msg                = $res['field124'] ?? '';

            // 统一状态
            $status = 2;
            if ($ret_Code == '00') {
                $status = 2;
            } elseif ($ret_Code == '01') {
                $status = 2;
            } elseif ($ret_Code == '02') {
                $status = 2;
            } elseif ($ret_Code == '03') {
                $status = 3;
            }

            $tmpArr['order_no'] = $platform_order_no;
            $tmpArr['status']   = $status;
            $tmpArr['msg']      = $msg;
        }


        return $tmpArr;
    }

    /**
     * @api {get} /admin/manualWithdrawals?startime=:startime&endtime=:endtime&account=:account&orderNo=:orderNo&platformId=:platformId&status=:status&isExport=:isExport&page=:page&perPage=:perPage 人工出款列表
     * @apiName ManualWithdrawals
     * @apiGroup Withdrawal
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} page 页数
     * @apiParam {Number} perPage 每页数据总数，默认为 20
     * @apiParam {String} startime 开始时间
     * @apiParam {String} endtime 结束时间
     * @apiParam {String} account 会员账号
     * @apiParam {String} orderNo 订单号
     * @apiParam {Number} platformId 出款平台ID
     * @apiParam {Number} status 状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
     * @apiParam {Number} isExport 导出execl，导出传 1，默认为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [{
     *           "id": 100,
     *           "platform_id": 1,
     *           "order_no": "201805020955526625552", // 单号
     *           "platform_order_no": null, // 流水号
     *           "member_id": 0, // 会员ID
     *           "account": null, // 会员账号
     *           "bank_no": "62283216452856241596", // 卡号
     *           "bank_name": "中国光大银行", // 银行
     *           "username": "ran062", // 姓名
     *           "amount": "50.00", // 金额
     *           "status": 0, // 状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
     *           "user_id": 1,
     *           "note": null, // 备注
     *           "created_at": "2018-05-02 09:55:52", // 当地
     *           "created_at_edt": "2018-04-26 08:29:43", // 美东)
     *           "platform": { // 出款平台
     *               "id": 1,
     *               "pay_out_type": 1, // 类型，需要转换为平台名称
     *               "no": "180021865588" // 商户号
     *           },
     *           "user": { // 操作人
     *               "id": 1,
     *               "username": "admin"
     *           }
     *       }],
     *       "first_page_url": "\/admin\/manualWithdrawals?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "\/admin\/manualWithdrawals?page=1",
     *       "next_page_url": null,
     *       "path": "\/admin\/manualWithdrawals",
     *       "per_page": 20,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *   }
     */
    public function manualWithdrawals(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        $page       = $getDatas['page'] ?? 1;
        $perPage    = $getDatas['perPage'] ?? 20;
        $startime   = $getDatas['startime'] ?? '';
        $endtime    = $getDatas['endtime'] ?? '';
        $account    = $getDatas['account'] ?? '';
        $orderNo    = $getDatas['orderNo'] ?? '';
        $status     = (isset($getDatas['status']) && strlen($getDatas['status']) > 0) ? intval($getDatas['status']) : -1;
        $platformId = $getDatas['platformId'] ?? 0;
        $isExport   = $getDatas['isExport'] ?? 0;

        Paginator::currentPathResolver(function () {
            return "/admin/manualWithdrawals";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $where = '`id` > 0';

        if ($startime) {
            $where .= " AND `created_at` >= CONVERT_TZ('{$startime}', '-04:00', '+08:00')";
        }

        if ($endtime) {
            $where .= " AND `created_at` <= CONVERT_TZ('{$endtime}', '-04:00', '+08:00')";
        }

        if ($account) {
            $where .= " AND `account` = '{$account}'";
        }

        if ($orderNo) {
            $where .= " AND (`order_no` = '{$orderNo}' OR `platform_order_no` = '{$orderNo}')";
        }

        if ($status >= 0) {
            $where .= " AND `status` = {$status}";
        }

        if ($platformId > 0) {
            $where .= " AND `platform_id` = {$platformId}";
        }

        $result = Withdrawal::with([
            'platform' => function ($query) {
                $query->select(['id', 'pay_out_type', 'no']);
            }, 'user' => function ($query) {
                $query->select(['id', 'username']);
            },
        ])->select([
            'id',
            'platform_id',
            'order_no',
            'platform_order_no',
            'member_id',
            'account',
            'bank_no',
            'bank_name',
            'username',
            'amount',
            'status',
            'user_id',
            'note',
            'created_at',
        ])->whereRaw($where);

        $result = $result->addSelect($this->db::raw("CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));

        if ($isExport == 0) {
            $result = $result->orderBy('id', 'desc')->paginate($perPage);

            return $response->withJson($result);
        } else {
            $result = $result->orderBy('id', 'DESC')->get()->toArray();

            $tmpArr = [
                1  => '天付宝',
                2  => '雅付',
                3  => '金海哲',
                4  => '泽圣',
                5  => '传化',
                6  => '开联通',
                7  => '众点',
                8  => '商码付',
                9  => '喜付',
                10 => '艾付',
                11 => 'Npay付',
                12 => '顺心付',
                13 => '天吉(仅充值)',
                14 => '迅捷',
                15 => '多宝(仅出款)',
                16 => 'Bingopay',
                17 => '易宝',
                18 => '新欣聚',
                19 => '佳友通',
                20 => '盖亚',
                21 => '青英',
                22 => '极云',
            ];

            $statusArr = [
                0 => '未处理',
                1 => '处理成功',
                2 => '处理中',
                3 => '处理失败',
                4 => '已退汇',
                5 => '其他',
            ];

            $header = ['出款平台', '商户号', '订单号', '出款流水号', '会员账号', '真实姓名', '银行卡号', '开户银行', '出款金额(元)', '出款时间(当地)', '出款时间(美东)', '状态', '备注', '操作人'];

            $datas = [];
            foreach ($result as $val) {
                $platformType = $val['platform']['pay_out_type'];
                $status       = $val['status'];
                array_push($datas, [
                    $tmpArr[$platformType],
                    $val['platform']['no'],
                    $val['order_no'],
                    $val['platform_order_no'],
                    $val['account'],
                    $val['username'],
                    $val['bank_no'],
                    $val['bank_name'],
                    $val['amount'],
                    $val['created_at'],
                    $val['created_at_edt'],
                    $statusArr[$status],
                    $val['note'],
                    $val['user']['username'],
                ]);
            }

            $filename = '人工出款记录-' . date('Ymd') . '.xls';

            $this->downExcel($header, $datas, $filename);
        }
    }
}
