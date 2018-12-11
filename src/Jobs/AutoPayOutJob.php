<?php

namespace Weiming\Jobs;

use Weiming\Jobs\BaseJob;
use Weiming\Libs\AgencyPayments\Aifu;
use Weiming\Libs\AgencyPayments\Bingo;
use Weiming\Libs\AgencyPayments\Chuanhua;
use Weiming\Libs\AgencyPayments\Duobao;
use Weiming\Libs\AgencyPayments\Gaiya;
use Weiming\Libs\AgencyPayments\Jiayoutong;
use Weiming\Libs\AgencyPayments\Jinhaizhe;
use Weiming\Libs\AgencyPayments\KaiLianTong;
use Weiming\Libs\AgencyPayments\Nongfu;
use Weiming\Libs\AgencyPayments\Shangma;
use Weiming\Libs\AgencyPayments\Shunxin;
use Weiming\Libs\AgencyPayments\Tianfubao;
use Weiming\Libs\AgencyPayments\Xifu;
use Weiming\Libs\AgencyPayments\Xinxinju;
use Weiming\Libs\AgencyPayments\Xunjie;
use Weiming\Libs\AgencyPayments\Yafu;
use Weiming\Libs\AgencyPayments\Yibao;
use Weiming\Libs\AgencyPayments\Zesheng;
use Weiming\Libs\AgencyPayments\Zhongdian;
use Weiming\Libs\AgencyPayments\Qingying;
use Weiming\Libs\AgencyPayments\Jiyun;
use Weiming\Libs\Crawler;
use Weiming\Libs\Utils;
use Weiming\Models\PayOut;
use Weiming\Models\Platform;

/**
 * 自动出款 Job
 */
class AutoPayOutJob extends BaseJob
{
    /**
     * 当前出款平台
     * @var null
     */
    private $payOutObj = null;

    /**
     * 当前出款单
     * @var null
     */
    private $payOut = null;

    /**
     * 当前出款平台信息
     * @var null
     */
    private $platform = null;

    /**
     * 爬虫http接口地址
     * @var String
     */
    private $crawlerUrl;

    /**
     * 全局配置
     * @var Array
     */
    private $config;

    public function perform()
    {
        /**
         * 提交数据格式
         * Array
         * (
         *     [orderNo] => 201703032107434712126
         *     [tranAmt] => 1000
         *     [acctName] => 张三
         *     [acctId] => 6226865984556213
         *     [mobile] => 13538675662
         *     [bankName] => 招商银行
         *     [province] => 广东
         *     [city] => 深圳
         *     [branch] => 新洲支行
         * )
         */
        $orderNo          = $this->args['orderNo'];
        $this->crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
        $this->payOut     = PayOut::where('order_no', '=', $orderNo)->first();
        $this->platform   = Platform::whereRaw("`id` = {$this->payOut->platform_id} AND `pay_out_type` = {$this->payOut->platform_type}")->first();
        $payOutType       = $this->platform->pay_out_type;
        $this->config     = ['parterNo' => $this->platform->no, 'parterKey' => $this->platform->key];
        if ($payOutType == 1) {
            $this->doTianfubao();
        } elseif ($payOutType == 2) {
            $this->doYafu();
        } elseif ($payOutType == 3) {
            $this->doJinhaizhe();
        } elseif ($payOutType == 4) {
            $this->doZesheng();
        } elseif ($payOutType == 5) {
            $this->doChuanhua();
        } elseif ($payOutType == 6) {
            $this->doKaiLianTong();
        } elseif ($payOutType == 7) {
            $this->doZhongdian();
        } elseif ($payOutType == 8) {
            $this->doShangma();
        } elseif ($payOutType == 9) {
            $this->doXifu();
        } elseif ($payOutType == 10) {
            $this->doAifu();
        } elseif ($payOutType == 11) {
            $this->doNongfu();
        } elseif ($payOutType == 12) {
            $this->doShunxin();
        } elseif ($payOutType == 14) {
            $this->doXunjie();
        } elseif ($payOutType == 15) {
            $this->doDuobao();
        } elseif ($payOutType == 16) {
            $this->doBingo();
        } elseif ($payOutType == 17) {
            $this->doYibao();
        } elseif ($payOutType == 18) {
            $this->doXinxinju();
        } elseif ($payOutType == 19) {
            $this->doJiayoutong();
        } elseif ($payOutType == 20) {
            $this->doGaiya();
        } elseif ($payOutType == 21) {
            $this->doQingying();
        } elseif ($payOutType == 22) {
            $this->doJiyun();
        }
        /*} elseif ($payOutType == 19) {
            $this->doJiayoutong();
        }*/
    }

    /**
     * 天付宝出款
     */
    private function doTianfubao()
    {
        $payOutObj = Tianfubao::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus   = $result;
            $tfb_serialno   = $result['tfb_serialno'] ?? '';
            $serialno_state = $result['serialno_state'] ?? 0;
            $serialno_desc  = $result['serialno_desc'] ?? '';
            if (empty($tfb_serialno) && $serialno_state == 2) {
                // 处理中，反查一次代付结果
                $payOutObj    = Tianfubao::getInstance($this->config);
                $payOutStatus = $payOutObj->generateSignature([
                    'orderNo'     => $this->args['orderNo'],
                    'tfbSerialNo' => $tfb_serialno,
                ], 'query')->sendRequest();
                $tfb_serialno   = $payOutStatus['tfb_serialno'] ?? '';
                $serialno_state = $payOutStatus['serialno_state'] ?? 2;
                $serialno_desc  = $payOutStatus['serialno_desc'] ?? '';
            } elseif (!empty($tfb_serialno) && $serialno_state == 1) {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $this->payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $this->payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $this->payOut->status = 1;
                }
            }
            // 统一状态
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

            $this->payOut->platform_order_no = $tfb_serialno;
            $this->payOut->platform_status   = $serialno_state; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $serialno_desc;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $serialno_desc,
                ]);
            }
        }
    }

    /**
     * 雅付出款
     */
    private function doYafu()
    {
        // 银行需要传入银行编码
        $bank = $this->args['bankName'];
        foreach ($this->settings['bank'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankName'] = $bankCode;
                break;
            }
        }
        $payOutObj = Yafu::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $orderNo      = $result['orderNo'] ?? '';
            $code         = $result['code'] ?? 0;
            $msg          = $result['msg'] ?? '';
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
                $payOutObj    = Yafu::getInstance($this->config);
                $payOutStatus = $payOutObj->generateSignature([
                    'merOrderNo' => $this->args['orderNo'],
                ], 'query')->sendRequest();
                $orderNo = $payOutStatus['casOrdNo'] ?? '';
                $code    = $payOutStatus['orderStatus'] ?? 4;
                $msg     = $tmpStatus[$code] ?? '未知';
            } elseif (!empty($orderNo) && $code == '000000') {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $this->payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $this->payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $this->payOut->status = 1;
                }
            }
            // 统一状态
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

            $this->payOut->platform_order_no = $orderNo;
            $this->payOut->platform_status   = $code; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 金海哲出款
     */
    private function doJinhaizhe()
    {
        $payOutObj = Jinhaizhe::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus  = $result;
            $sy_request_no = $result['sy_request_no'] ?? '';
            $status        = $result['code'] ?? ($result['status'] ?? 1);
            $msg           = $result['msg'] ?? ($result['origData']['retMsg'] ?? '');
            // if (empty($sy_request_no) && $status == 1) {
            //     // 处理中，反查一次代付结果
            //     $tmpStatus = [
            //         1 => '处理中',
            //         2 => '处理成功',
            //         3 => '处理失败',
            //         4 => '已退汇',
            //     ];
            //     $payOutObj   = Jinhaizhe::getInstance($this->config);
            //     $payOutStatus = $payOutObj->generateSignature([
            //         'orderNo' => $this->args['orderNo'],
            //     ], 'query')->sendRequest();
            //     $sy_request_no = $payOutStatus['sy_request_no'] ?? '';
            //     $status        = $payOutStatus['status'] ?? 1;
            //     $msg           = $payOutStatus['origData']['retMsg'] ?? '未知';
            // } else
            if (!empty($sy_request_no) && $status == 2) {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $this->payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $this->payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $this->payOut->status = 1;
                }
            }
            // 统一状态
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

            $this->payOut->platform_order_no = $sy_request_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 泽圣出款
     */
    private function doZesheng()
    {
        $payOutObj = Zesheng::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $orderId      = $result['orderId'] ?? '';
            $status       = $result['state'] ?? '01';
            $msg          = $result['errorMsg'] ?? ($result['origData']['msg'] ?? '');
            if (!empty($orderId) && $status == '01') {
                // 处理中，反查一次代付结果
                $payOutObj    = Zesheng::getInstance($this->config);
                $payOutStatus = $payOutObj->generateSignature([
                    'orderNo' => $this->args['orderNo'],
                ], 'query')->sendRequest();
                $orderId = $payOutStatus['orderId'] ?? '';
                $status  = $payOutStatus['state'] ?? '01';
                $msg     = $payOutStatus['errorMsg'] ?? ($payOutStatus['origData']['msg'] ?? '未知');
                if (!empty($orderId) && $status == '00') {
                    // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                    $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                        'act'     => 'updateStatus',
                        'id'      => $this->payOut->wid,
                        'status'  => 1, // 确定
                        'account' => $this->payOut->account,
                    ]);
                    if (strpos($crawlerRes, 'true') !== false) {
                        // 更新 确定 状态
                        $this->payOut->status = 1;
                    }
                }
            }
            // 统一状态
            if ($status == '00') {
                $status = 1;
            } elseif ($status == '01' || $status == '90') {
                $status = 2;
            } elseif ($status == '02') {
                $status = 3;
            } else {
                $status = 5;
            }

            $this->payOut->platform_order_no = $orderId;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 传化出款
     */
    private function doChuanhua()
    {
        $payOutObj = Chuanhua::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $code         = $result['code'] ?? '';
            $status       = $result['result'] ?? '';
            $msg          = $result['msg'] ?? '';
            if ($status == 'success') {
                // 处理中，10秒后反查一次代付结果
                $payOutObj    = Chuanhua::getInstance($this->config);
                $payOutStatus = $payOutObj->generateSignature([
                    'orderNo' => $this->args['orderNo'],
                ], 'query')->sendRequest();

                $status  = $payOutStatus['result'] ?? '';
                $msg     = $payOutStatus['msg'] ?? '';
                $state   = $payOutStatus['data']['status'] ?? '';
                $orderId = $payOutStatus['data']['businessrecordnumber'] ?? '';

                if (!empty($orderId) && strpos($state, '成功') !== false) {
                    // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                    $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                        'act'     => 'updateStatus',
                        'id'      => $this->payOut->wid,
                        'status'  => 1, // 确定
                        'account' => $this->payOut->account,
                    ]);
                    if (strpos($crawlerRes, 'true') !== false) {
                        // 更新 确定 状态
                        $this->payOut->status = 1;
                    }
                }
                // 统一状态
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

            $this->payOut->platform_order_no = $orderId;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $state;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $state,
                ]);
            }
        }
    }

    /**
     * 开联通出款
     */
    private function doKaiLianTong()
    {
        $payOutObj = KaiLianTong::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $code         = $result['response']['envelope']['body']['responseCode'] ?? '';
            $status       = $result['response']['envelope']['body']['status'] ?? ''; // 非法交易 INVALID、已受理 CREATED、交易中 TX_BEGIN、交易成功 TX_SUCCESS、交易失败 TX_FAIL、关闭 CLOSED
            $msg          = $result['response']['envelope']['body']['responseMsg'] ?? '';

            if ($code == 'E0000') {
                // 已受理、交易中
                if ($status == 'CREATED' || $status == 'TX_BEGIN') {
                    $payOutObj    = KaiLianTong::getInstance($this->config);
                    $payOutStatus = $payOutObj->generateSignature([
                        'orderNo' => $this->args['orderNo'],
                    ], 'query')->sendRequest();

                    $code   = $payOutStatus['response']['envelope']['body']['responseCode'] ?? '';
                    $status = $payOutStatus['response']['envelope']['body']['status'] ?? '';
                    $msg    = $payOutStatus['response']['envelope']['body']['responseMsg'] ?? '';
                }

                // 交易成功
                if ($status == 'TX_SUCCESS') {
                    // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                    $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                        'act'     => 'updateStatus',
                        'id'      => $this->payOut->wid,
                        'status'  => 1, // 确定
                        'account' => $this->payOut->account,
                    ]);
                    if (strpos($crawlerRes, 'true') !== false) {
                        // 更新 确定 状态
                        $this->payOut->status = 1;
                    }
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

            $this->payOut->platform_order_no = 'R' . date('YmdHis') . rand(1000, 9999);
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 众点出款
     */
    private function doZhongdian()
    {
        $payOutObj = Zhongdian::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $code         = $result['result_code'] ?? '';
            $status       = $result['result_code'] ?? ''; // 0 成功、非0失败
            $msg          = $result['result_msg'] ?? '';
            $orderId      = $result['transaction_id'] ?? '';

            // 交易失败
            // if ($status != 0) {
            //     $payOutObj    = Zhongdian::getInstance($this->config);
            //     $payOutStatus = $payOutObj->generateSignature([
            //         'orderNo' => $this->args['orderNo'],
            //     ], 'query')->sendRequest();

            //     $code    = $result['result_code'] ?? '';
            //     $status  = $result['result_code'] ?? '';
            //     $msg     = $result['result_msg'] ?? '';
            //     $orderId = $result['transaction_id'] ?? '';
            // }

            // 交易成功
            if ($status == 0) {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $this->payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $this->payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $this->payOut->status = 1;
                }
            }

            // 统一状态，0 成功、非0失败
            if ($status == 0) {
                $status = 1;
            } else {
                $status = 5;
            }

            $this->payOut->platform_order_no = $orderId;
            $this->payOut->platform_status   = $status; // 0 成功、非0失败
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 商码付出款
     */
    private function doShangma()
    {
        // 银行需要传入银行编码
        $bank = $this->args['bankName'];
        foreach ($this->settings['shangma'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        $payOutObj = Shangma::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultCode   = $result['resultCode'] ?? '01';
            $resultMsg    = $result['resultMsg'] ?? '';
            $orderNo      = $result['outTradeNo'] ?? '';
            $returnCode   = $result['returnCode'] ?? -1;
            if (!empty($orderNo) && $resultCode == '00' && $returnCode == 2) {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $this->payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $this->payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $this->payOut->status = 1;
                }
            }
            // 统一状态
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

            $this->payOut->platform_order_no = $orderNo;
            $this->payOut->platform_status   = $returnCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);
            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * 喜付出款
     */
    private function doXifu()
    {
        // 银行需要传入文档里指定的银行名称，少一个字都不行，fuck，真尼玛蛋疼！
        $bank = $this->args['bankName'];
        foreach ($this->settings['xifu'] as $keyword => $bankName) {
            if (strpos($bank, $keyword) !== false) {
                $this->args['bankName'] = $bankName;
                break;
            }
        }
        $payOutObj = Xifu::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultCode   = $result['respCode'] ?? 'F9999';
            $resultMsg    = $result['respMessage'] ?? '';

            // 统一状态
            if ($resultCode == 'S0001') {
                $resultCode = 2;
            } else {
                $resultCode = 3;
            }

            $this->payOut->platform_order_no = ''; // fuck 抹油流水单号，只能造假了
            $this->payOut->platform_status   = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * 艾付出款
     */
    private function doAifu()
    {
        // 银行需要传入文档里指定的银行编码，fuck，真尼玛蛋疼！
        $bank = $this->args['bankName'];
        foreach ($this->settings['aifu'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        $payOutObj = Aifu::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultCode   = $result['result_code'] ?? '';
            $resultMsg    = $result['result_msg'] ?? '';
            $resultFlag   = $result['result'] ?? 'H';
            $orderNo      = $result['order_no'] ?? '';

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

            $this->payOut->platform_order_no = $orderNo;
            $this->payOut->platform_status   = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * Nong付出款
     */
    private function doNongfu()
    {
        // 银行需要传入文档里指定的银行编码，fuck，真尼玛蛋疼！
        $bank = $this->args['bankName'];
        foreach ($this->settings['nongfu'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        $payOutObj = Nongfu::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultCode   = $result['code'] ?? '1002';
            $resultMsg    = $result['msg'] ?? '';
            $resultFlag   = $result['success'] ?? 0;

            // if ($resultFlag == 1 && $resultCode == '1001') {
            //     // 交易成功，修改 BBIN 后台出款单状态为 1 确定
            //     $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
            //         'act'     => 'updateStatus',
            //         'id'      => $this->payOut->wid,
            //         'status'  => 1, // 确定
            //         'account' => $this->payOut->account,
            //     ]);
            //     if (strpos($crawlerRes, 'true') !== false) {
            //         // 更新 确定 状态
            //         $this->payOut->status = 1;
            //     }
            // }

            // 统一状态
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

            $this->payOut->platform_order_no = 'NO_ORDER_NUMBER';
            $this->payOut->platform_status   = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * 顺心付出款
     */
    private function doShunxin()
    {
        // 银行需要传入文档里指定的银行编码，fuck，真尼玛蛋疼！
        $bank = $this->args['bankName'];
        foreach ($this->settings['shunxin'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        $payOutObj = Shunxin::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultMsg    = $result['respDesc'] ?? '';
            $resultFlag   = $result['respCode'] ?? '05';
            $orderNo      = $result['batchNo'] ?? '';

            // 统一状态
            $resultCode = 3;
            if ($resultFlag == '00') {
                $resultCode = 2;
            }

            $this->payOut->platform_order_no = $orderNo;
            $this->payOut->platform_status   = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * 迅捷付出款
     */
    private function doXunjie()
    {
        $payOutObj = Xunjie::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultMsg    = $result['respDesc'] ?? '';
            $resultFlag   = $result['respCode'] ?? '';
            $orderNo      = $result['serialId'] ?? '';

            if ($resultFlag == '0000') {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $this->payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $this->payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $this->payOut->status = 1;
                }
            }

            // 统一状态
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

            $this->payOut->platform_order_no = $orderNo;
            $this->payOut->platform_status   = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * 多宝出款
     */
    private function doDuobao()
    {
        $payOutObj = Duobao::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $resultCode   = $result['status'] ?? 'error'; // success
            $resultMsg    = $result['msg'] ?? '';
            $settleid     = $result['settleid'] ?? '';

            // 统一状态
            $status = 0;
            if ($resultCode == 'success') {
                $status = 2;
            } elseif ($resultCode == 'error') {
                $status = 3;
            }

            $this->payOut->platform_order_no = $settleid;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $resultMsg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $resultMsg,
                ]);
            }
        }
    }

    /**
     * Bingo出款
     */
    private function doBingo()
    {
        // 银行需要传入文档里指定的银行编码，fuck，真尼玛蛋疼！
        $bank = $this->args['bankName'];
        foreach ($this->settings['bingo'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        // 还得先查询余额，确定哪个账户又余额才能出款，真是异类，不走寻常路啊！
        list($accountCode, $accountType) = Utils::getBingoCanUseBalance($this->config, $this->args['tranAmt']);
        $this->args['accountCode']       = $accountCode;
        $this->args['accountType']       = $accountType;
        // 代付下发
        $payOutObj = Bingo::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $respCode     = $result['respCode'] ?? '';
            $code         = $result['key'] ?? '';
            $msg          = $result['msg'] ?? '';

            // 统一状态
            $status = 5;
            if ($respCode == '00') {
                $status = 3;
                if ($code == '00') {
                    $status = 1;
                } elseif ($code == '05') {
                    $status = 2;
                }
            }

            $this->payOut->platform_order_no = 'NO_ORDER_NUMBER';
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * Yibao出款
     */
    private function doYibao()
    {
        // 银行需要传入文档里指定的银行编码，fuck，真尼玛蛋疼！
        $bank = $this->args['bankName'];
        foreach ($this->settings['yibao'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        // 代付下发
        $payOutObj = Yibao::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus = $result;
            $ret_Code     = $result['ret_Code'] ?? '';
            $r1_Code      = $result['r1_Code'] ?? '';
            $msg          = $result['error_Msg'] ?? '';

            // 统一状态
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

            $this->payOut->platform_order_no = 'NO_ORDER_NUMBER';
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 新欣聚出款
     */
    private function doXinxinju()
    {
        // 代付下发
        $payOutObj = Xinxinju::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus      = $result;
            $ret_Code          = $result['field039'] ?? '';
            $platform_order_no = $result['field062'] ?? '';
            $msg               = $result['field124'] ?? '';

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

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 佳友通出款
     */
    private function doJiayoutong()
    {
        // 代付下发
        $payOutObj = Jiayoutong::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $ret_Code          = $result['code'] ?? '';
            $resStatus         = $result['status'] ?? '';
            $platform_order_no = $result['businessNo'] ?? '';
            $msg               = $result['describe'] ?? '';

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

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($result);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /**
     * 佳友通出款
     */
    private function doGaiya(){
        // 代付下发
        $bank = $this->args['bankName'];
        foreach ($this->settings['gaiya'] as $bankName => $bankCode) {
            if (strpos($bank, $bankName) !== false) {
                $this->args['bankCode'] = $bankCode;
                break;
            }
        }
        $payOutObj = Gaiya::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $ret_Code          = $result['success'] ?? '';
            $resStatus         = $result['respCode'] ?? '';
            $platform_order_no = $result['merOrderId'] ?? '';
            $msg               = $result['respMsg'] ?? '';

            // 统一状态
            $status = 3;
            if($ret_Code == 1){
                if ($resStatus === 0) {
                    $status = 1;
                }else{
                    $status = 2;
                }
            }

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($result);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /*
     * 青英
     * */

    private function doQingying()
    {
        // 代付下发
        $payOutObj = Qingying::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus      = $result;
            $ret_Code          = $result['field039'] ?? '';
            $platform_order_no = $result['field062'] ?? '';
            $msg               = $result['field124'] ?? '';

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

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

    /*
     * 极云
     * */

    private function doJiyun()
    {
        // 代付下发
        $payOutObj = Jiyun::getInstance($this->config);
        $result    = $payOutObj->generateSignature($this->args, 'payment')->sendRequest();
        if ($result) {
            $payOutStatus      = $result;
            $ret_Code          = $result['field039'] ?? '';
            $platform_order_no = $result['field062'] ?? '';
            $msg               = $result['field124'] ?? '';

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

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($payOutStatus);

            if ($this->payOut->save()) {
                // 备注
                $crawlerRes = Crawler::getInstance()->updatePayOutData($this->crawlerUrl, [
                    'act'     => 'updateRemark',
                    'id'      => $this->payOut->wid,
                    'content' => $msg,
                ]);
            }
        }
    }

}
