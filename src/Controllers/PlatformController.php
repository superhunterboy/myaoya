<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\AgencyPayments\Aifu as AgencyAifu;
use \Weiming\Libs\AgencyPayments\Bingo as AgencyBingo;
use \Weiming\Libs\AgencyPayments\Chuanhua as AgencyChuanhua;
use \Weiming\Libs\AgencyPayments\Duobao as AgencyDuobao;
use \Weiming\Libs\AgencyPayments\Gaiya as AgencyGaiya;
use \Weiming\Libs\AgencyPayments\Gft;
use \Weiming\Libs\AgencyPayments\GPpay as AgencyGPpay;
use \Weiming\Libs\AgencyPayments\Jiayoutong as AgencyJiayoutong;
use \Weiming\Libs\AgencyPayments\Jinhaizhe as AgencyJinhaizhe;
use \Weiming\Libs\AgencyPayments\Jiyun as AgencyJiyun;
use \Weiming\Libs\AgencyPayments\KaiLianTong as AgencyKaiLianTong;
use \Weiming\Libs\AgencyPayments\Nongfu as AgencyNongfu;
use \Weiming\Libs\AgencyPayments\Qingying as AgencyQingying;
use \Weiming\Libs\AgencyPayments\Shangma as AgencyShangma;
use \Weiming\Libs\AgencyPayments\Shunxin as AgencyShunxin;
use \Weiming\Libs\AgencyPayments\Tianfubao as AgencyTianfubao;
use \Weiming\Libs\AgencyPayments\Xifu as AgencyXifu;
use \Weiming\Libs\AgencyPayments\Xinxinju as AgencyXinxinju;
use \Weiming\Libs\AgencyPayments\Xunjie as AgencyXunjie;
use \Weiming\Libs\AgencyPayments\Yafu as AgencyYafu;
use \Weiming\Libs\AgencyPayments\Yibao as AgencyYibao;
use \Weiming\Libs\AgencyPayments\Zesheng as AgencyZesheng;
use \Weiming\Libs\AgencyPayments\Zhongxin as AgencyZhongxin;
use \Weiming\Libs\AgencyPayments\Xianfeng as AgencyXianfeng;
use \Weiming\Libs\AgencyPayments\Tongfu as AgencyTongfu;
use \Weiming\Libs\AgencyPayments\SDpay as AgencySDpay;
use \Weiming\Libs\AgencyPayments\Huitian as AgencyHuitian;
use \Weiming\Libs\Payments\Aifu;
use \Weiming\Libs\Payments\Bingo;
use \Weiming\Libs\Payments\Duobao;
use \Weiming\Libs\Payments\Gaiya;
use \Weiming\Libs\Payments\JinhaizheNew;
use \Weiming\Libs\Payments\Nongfu;
use \Weiming\Libs\Payments\Shangma;
use \Weiming\Libs\Payments\Shunxin;
use \Weiming\Libs\Payments\Tianfubao;
use \Weiming\Libs\Payments\Tianji;
use \Weiming\Libs\Payments\Xifu;
use \Weiming\Libs\Payments\Xunjie;
use \Weiming\Libs\Payments\YafuNew;
use \Weiming\Libs\Payments\Zesheng;
use \Weiming\Libs\SMServer;
use \Weiming\Libs\Utils;
use \Weiming\Models\Code;
use \Weiming\Models\PayOut;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;
use \Weiming\Models\RechargeLink;
use \Weiming\Models\Withdrawal;

class PlatformController extends BaseController
{
    /**
     * @api {post} /admin/addPlatform 添加出款平台
     * @apiName AddPlatform
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} company_id 业务平台id
     * @apiParam {Number} pay_out_type 出款平台类型 1、天付宝 2、雅付 3、金海哲 4、泽圣
     * @apiParam {String} no 商户号
     * @apiParam {String} key 秘钥
     * @apiParam {String} callback_url 异步回调地址
     * @apiParam {String} notify_url 同步回调地址
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功",
     *       "data": []
     *   }
     */
    public function add(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '添加失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        if (Platform::create($postDatas)) {
            $result['status'] = 0;
            $result['msg']    = '添加成功';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deletePlatform/:id 删除出款平台
     * @apiName DeletePlatform
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款平台id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "删除成功",
     *       "data": []
     *   }
     */
    public function delete(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $id     = $args['id'];
        if ($id > 0) {
            $payOutCount       = PayOut::where('platform_id', '=', $id)->count();
            $rechargeLinkCount = RechargeLink::where('platform_id', '=', $id)->count();
            $rechargeCount     = Recharge::where('platform_id', '=', $id)->count();
            $withdrawalCount   = Withdrawal::where('platform_id', '=', $id)->count();
            if ($payOutCount == 0 && $rechargeLinkCount == 0 && $rechargeCount == 0 && $withdrawalCount == 0 && Platform::find($id)->delete()) {
                $result['status'] = 0;
                $result['msg']    = '删除成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/editPlatform/:id 修改出款平台
     * @apiName EditPlatform
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款平台id
     *
     * @apiParam {Number} company_id 业务平台id
     * @apiParam {Number} pay_out_type 出款平台类型 1、天付宝 2、雅付 3、金海哲 4、泽圣
     * @apiParam {String} no 商户号
     * @apiParam {String} key 秘钥
     * @apiParam {String} callback_url 异步回调地址
     * @apiParam {String} notify_url 同步回调地址
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "修改成功",
     *       "data": []
     *   }
     */
    public function edit(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '修改失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        if (Platform::find($args['id'])->update($postDatas)) {
            $result['status'] = 0;
            $result['msg']    = '修改成功';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/platforms?page=:page&perPage=:perPage&type=:type 出款平台列表
     * @apiName Platforms
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 当前页
     * @apiParam {Number} perPage 每页数据条数
     * @apiParam {Number} type 出款平台类型 ID，1、天付宝, 2、雅付, 3、金海哲, 4、泽圣, 5、传化, 6、开联通, 7、众点, 8、商码付, 9、喜付, 10、艾付, 11、Npay付, 12、顺心付, 13、天吉(仅充值), 14、迅捷, 15、多宝(仅出款)
     * @apiParam {String} no 商户号
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 1,
     *               "company_id": 1,       // 所属业务平台
     *               "pay_out_type": 1,      // 出款平台类型 1、天付宝, 2、雅付, 3、金海哲, 4、泽圣, 5、传化, 6、开联通, 7、众点, 8、商码付, 9、喜付, 10、艾付, 11、Npay付, 12、顺心付, 13、天吉(仅充值), 14、迅捷, 15、多宝(仅出款)
     *               "no": "1800218655",    // 商户号
     *               "key": "{\"pubKey\":\"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCjDrkoVbyv4jTxeKtKEiK2mZiezQvfJV3sGhiwOnB+By5sa5Sa6Ls4dt5AGVqKHxyQVKRpu/utwtEt2MijWx45P1y2xGe7oDz2hUXP0j8sSa1NP26TmWHwO7czgJxxrdJ6RNqskSfjwsa5YMsqmcrumxUIxeCg5EOkgU26bnPoZQIDAQAB\",\"priKey\":\"MIICWwIBAAKBgQCnVrbDE5l3C84c0TEPKhMIfyLcsOvb76QwRBduyUx6M5MdZLmmxZtf03vsUR5nM1Olsbca+WLWsRgqAJ3P9kkepx8NHsFh5kxvrcf9HS2CCuKYTwl2H2PiZNEJZrO5bQzritPGpV4CSFi17tQgNiDGiQvcubh0gPznFz2JRjUdeQIDAQABAoGACad6EdUepHyc3pK/FtXhlTrogvz+DolL2rEC/RRGAkLZePenslRyO1wTKTYg3+lZKRUQsFC1ABflgf7ZG/dkjnU+SdZFnQyk2BSWON4OPdqVu4fXbUVFpo9jqr3A3h8UwogTKjVCc0gUiKX43tvzXpquOO+7Wo+nmxtoMEOyRvUCQQDcL1DgSqHvh9T0j5XsxSP20eAfpdgXatASAlqxE3gaMzrczf5AYXZfQ8n1aEAVB3dSyApi9OMoQoRu9r4Us5SrAkEAwo7XW0bKpOrgorib+1FrxFWjKDcO/Jp/DuHEWOTZzZxxC+KOV0Pffh4DTIfIPafc2f35V1qI0KAy7E1Q2s3uawJAeLpA4BpL3ChgLZAlJmOZqpW9C91FNOHOIHHGBF2mdscsOTGBfQ9zdhyXOcXljSJA3NTuTP/1JXgDYUxNQNCObQJAbdQ89iXsV1TQIoucYi8AawD87cLHDwoYF8qTEKt/2HYrys3GFLUYARXoPteXzlZochbRHuLYwQzuMj8jGaGOqQJADSMHFkAhQPcp56eziE/x/qZnAMHXUBqDjO/+xk7JE+MuEVRxs5WmPKW9XLJ/Hp5J9cwcdaiwIqoyj7D6LcTXYg==\",\"saltKey\":\"n-Ld08HWB8\"}",    // 商户key
     *               "callback_url": "", // 异步回调地址
     *               "notify_url": "", // 同步回调地址
     *               "balance": 0.00, // 余额
     *               "enabled": 1, // 是否启用
     *               "created_at": "2017-09-22 09:54:45",
     *               "updated_at": "2017-09-22 09:54:48"
     *           }
     *       ],
     *       "first_page_url": "/admin/platforms?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "/admin/platforms?page=1",
     *       "next_page_url": null,
     *       "path": "/admin/platforms",
     *       "per_page": 10,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *   }
     */
    public function query(Request $request, Response $response, $args)
    {
        $result   = [];
        $getDatas = $request->getQueryParams();
        $page     = $getDatas['page'] ?? 1;
        $perPage  = $getDatas['perPage'] ?? 10;
        $type     = $getDatas['type'] ?? 0;
        $pt       = $getDatas['pt'] ?? 2; // 0 会员自动出款、1 手动出款、>1 二者都是
        $no       = $getDatas['no'] ?? '';

        // 数据权限
        $companyIds      = $this->jwt->userInfo->company_ids;
        $currentUserType = $this->jwt->userInfo->type;

        Paginator::currentPathResolver(function () {
            return "/admin/platforms";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        if (in_array($pt, [0, 1])) {
            $result = Platform::whereRaw("`type` = {$pt}");
        } else {
            $result = Platform::whereRaw("`type` IN (0, 1)");
        }

        if ($type) {
            $result = $result->where('pay_out_type', '=', $type);
        }

        if ($no) {
            $result = $result->where('no', 'LIKE', "%{$no}%");
        }

        // 管理员看全部业务平台
        if ($currentUserType != 1) {
            $result = $result->whereRaw("`company_id` IN ({$companyIds})");
        }

        $result = $result->orderBy('enabled', 'desc')->orderBy('type', 'desc')->orderBy('updated_at', 'desc')->paginate($perPage);

        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateAmountLimit/:id 更新出款平台额度限制
     * @apiName UpdateAmountLimit
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款平台id
     * @apiParam {Number} start_amount_limit 起始出款平台额度限制
     * @apiParam {Number} end_amount_limit 结束出款平台额度限制
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function updateAmountLimit(Request $request, Response $response, $args)
    {
        $result           = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $postDatas        = $request->getParsedBody();
        $id               = $args['id'];
        $startAmountLimit = $postDatas['start_amount_limit'] ?? 0;
        $endAmountLimit   = $postDatas['end_amount_limit'] ?? 0;
        if ($id > 0) {
            if ($startAmountLimit > $endAmountLimit) {
                $result['msg'] = '起始额度不能大于结束额度';
                return $response->withJson($result);
            }
            // 判断区间是否有重叠的情况
            $platforms = Platform::where('id', '!=', $id)->get()->toArray();
            foreach ($platforms as $platform) {
                if ($platform['start_amount_limit'] <= $endAmountLimit && $platform['end_amount_limit'] >= $startAmountLimit) {
                    $result['msg'] = '额度区间重叠的情况';
                    return $response->withJson($result);
                }
            }
            $platform                     = Platform::where('id', '=', $id)->first();
            $platform->start_amount_limit = $startAmountLimit;
            $platform->end_amount_limit   = $endAmountLimit;
            if ($platform->save()) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
                $result['data']   = $platform;
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/changePlatform/:id 启用禁用出款平台
     * @apiName ChangePlatform
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款平台id
     * @apiParam {String} code 验证码
     * @apiParam {String} type 操作类型、0 为自动出款 1 为手动出款，默认为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function changePlatform(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '参数错误，更新失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $id        = $args['id'];
        $code      = $postDatas['code'] ?? '';
        $type      = $postDatas['type'] ?? 0;
        if ($id > 0 && $code) {
            // 验证短信
            $smsInfo  = $this->settings->get('sms');
            $code     = trim($code);
            $mobile   = $smsInfo['mobile'] ?? '';
            $smserver = new SMServer($smsInfo['appKey'], $smsInfo['appSecret'], 'curl');
            $resData  = $smserver->sendSMSVerifycode($mobile, $code);
            if ($resData && $resData['code'] == 200) {
                if (Code::where('mobile', '=', $mobile)->where('code', '=', $code)->update(['ok' => 1])) {
                    $platform = Platform::where('id', '=', $id)->first();
                    if ($type == 0) {
                        $platform->enabled = $platform->enabled == 1 ? 0 : 1;
                    } elseif ($type == 1) {
                        $platform->type = $platform->type == 1 ? 0 : 1;
                    }
                    if ($platform->save()) {
                        $result['status'] = 0;
                        $result['msg']    = '更新成功';
                        $result['data']   = $platform;
                    }
                }
            } else {
                $result['msg']  = '短信验证失败';
                $result['data'] = $resData;
            }
        }
        return $response->withJson($result);
    }
    // public function changePlatform(Request $request, Response $response, $args)
    // {
    //     $result = ['status' => 1, 'msg' => '切换失败', 'data' => []];
    //     $id     = $args['id'];
    //     if ($id > 0) {
    //         $platform = Platform::where('id', '=', $id)->first();
    //         if (Platform::count() > 1 && Platform::where('company_id', '=', $platform->company_id)->where('id', '<>', $id)->where('type', '=', 0)->update(['enabled' => 0]) && Platform::where('company_id', '=', $platform->company_id)->where('id', '=', $id)->update(['enabled' => 1])) {
    //             $result['status'] = 0;
    //             $result['msg']    = '切换成功';
    //         }
    //     }
    //     return $response->withJson($result);
    // }

    /**
     * @api {get} /admin/queryBalance/:id 出款平台余额查询
     * @apiName QueryBalance
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款平台id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "余额查询成功",
     *       "data": {
     *           "balance": 0.01
     *       }
     *   }
     */
    public function queryBalance(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '余额查询失败', 'data' => ['balance' => 0]];
        $id     = $args['id'] ?? 0;
        if ($id > 0) {
            $payOutObj  = null;
            $platform   = Platform::where('id', '=', $id)->first();
            $payOutType = $platform->pay_out_type;
            $config     = ['parterNo' => $platform->no, 'parterKey' => $platform->key];
            if ($payOutType == 1) {
                $payOutObj = AgencyTianfubao::getInstance($config);
            } elseif ($payOutType == 2) {
                $payOutObj = AgencyYafu::getInstance($config);
            } elseif ($payOutType == 3) {
                $payOutObj = AgencyJinhaizhe::getInstance($config);
            } elseif ($payOutType == 4) {
                $payOutObj = AgencyZesheng::getInstance($config);
            } elseif ($payOutType == 5) {
                $payOutObj = AgencyChuanhua::getInstance($config);
            } elseif ($payOutType == 6) {
                $payOutObj = AgencyKaiLianTong::getInstance($config);
            } elseif ($payOutType == 8) {
                $payOutObj = AgencyShangma::getInstance($config);
            } elseif ($payOutType == 9) {
                $payOutObj = AgencyXifu::getInstance($config);
            } elseif ($payOutType == 10) {
                $payOutObj = AgencyAifu::getInstance($config);
            } elseif ($payOutType == 11) {
                $payOutObj = AgencyNongfu::getInstance($config);
            } elseif ($payOutType == 12) {
                $payOutObj = AgencyShunxin::getInstance($config);
            } elseif ($payOutType == 14) {
                $payOutObj = AgencyXunjie::getInstance($config);
            } elseif ($payOutType == 15) {
                $payOutObj = AgencyDuobao::getInstance($config);
            } elseif ($payOutType == 16) {
                $payOutObj = AgencyBingo::getInstance($config);
            } elseif ($payOutType == 17) {
                $payOutObj = AgencyYibao::getInstance($config);
            } elseif ($payOutType == 18) {
                $payOutObj = AgencyXinxinju::getInstance($config);
            } elseif ($payOutType == 19) {
                $payOutObj = AgencyJiayoutong::getInstance($config);
            } elseif ($payOutType == 20) {
                $payOutObj = AgencyGaiya::getInstance($config);
            } elseif ($payOutType == 21) {
                $payOutObj = AgencyQingying::getInstance($config);
            } elseif ($payOutType == 22) {
                $payOutObj = AgencyJiyun::getInstance($config);
            } elseif ($payOutType == 23) {
                $payOutObj = RHPay::getInstance($config);
            } elseif ($payOutType == 24) {
                $payOutObj = Gft::getInstance($config);
            } elseif ($payOutType == 25) {
                $payOutObj = AgencyZhongxin::getInstance($config);
            } elseif ($payOutType == 26) {
                $payOutObj = AgencyGPpay::getInstance($config);
            } elseif ($payOutType == 27) {
                $config['callbackUrl'] = $platform->callback_url;
                $payOutObj = AgencyXianfeng::getInstance($config);
            } elseif ($payOutType == 28) {
                $config['callbackUrl'] = $platform->callback_url;
                $payOutObj = AgencyTongfu::getInstance($config);
            } elseif ($payOutType == 29) {
                $config['callbackUrl'] = $platform->callback_url;
                $payOutObj = AgencySDpay::getInstance($config);
            } elseif ($payOutType == 30) {
                $payOutObj = AgencyHuitian::getInstance($config);
            }
            if ($payOutObj) {
                $availableBalance = 0;
                $res              = $payOutObj->generateSignature(['orderNo' => Utils::getOrderId(date('YmdHis'))], 'balanceQuery')->sendRequest();
                // print_r($res);die;
                if ($res) {
                    if ($payOutType == 1) {
                        if (isset($res['account_status']) && isset($res['available_balance']) && $res['account_status'] == 1) {
                            $availableBalance = $res['available_balance'] / 100;
                        }
                    } elseif ($payOutType == 2) {
                        if (isset($res['code']) && isset($res['acT0']) && $res['code'] == '000000') {
                            $availableBalance = $res['acT0'];
                        }
                    } elseif ($payOutType == 3) {
                        if (isset($res['avail_amount'])) {
                            $availableBalance = $res['avail_amount'] / 100;
                        }
                    } elseif ($payOutType == 4) {
                        if (isset($res['availableBalance'])) {
                            $availableBalance = $res['availableBalance'] / 100;
                        }
                    } elseif ($payOutType == 5) {
                        if (isset($res['data']['balance'])) {
                            $availableBalance = $res['data']['balance'];
                        }
                    } elseif ($payOutType == 6) {
                        if (isset($res['response']['envelope']['body']['accountBalance'])) {
                            $availableBalance = $res['response']['envelope']['body']['accountBalance'] / 100;
                        }
                    } elseif ($payOutType == 8) {
                        if (isset($res['resultCode']) && isset($res['accBalance']) && $res['resultCode'] == '00') {
                            $availableBalance = $res['accBalance'] / 100;
                        }
                    } elseif ($payOutType == 9) {
                        if (isset($res['status']) && isset($res['reason']) && $res['status'] == 'succ') {
                            $availableBalance = $res['reason'] / 100;
                        }
                    } elseif ($payOutType == 10) {
                        if (isset($res['result_code']) && isset($res['balance']) && $res['result_code'] == '000000') {
                            $availableBalance = $res['balance'];
                        }
                    } elseif ($payOutType == 11) {
                        if (isset($res['success']) && isset($res['balance']) && $res['success'] == 1) {
                            $availableBalance = $res['balance'] / 100;
                        }
                    } elseif ($payOutType == 12) {
                        if (isset($res['respCode']) && $res['respCode'] == '00' && isset($res['usableAmt'])) {
                            $availableBalance = $res['usableAmt']; // 接口不能用中，不知啥时候恢复，这里不知道是元还是分
                        }
                    } elseif ($payOutType == 14) {
                        if (isset($res['respCode']) && $res['respCode'] == '0000' && isset($res['totalBalance'])) {
                            $availableBalance = $res['totalBalance'] / 100;
                        }
                    } elseif ($payOutType == 15) {
                        if (isset($res['status']) && $res['status'] == 'success' && isset($res['enableAmt'])) {
                            $availableBalance = $res['enableAmt'];
                        }
                    } elseif ($payOutType == 16) {
                        if (isset($res['respCode']) && $res['respCode'] == '00' && isset($res['key']) && $res['key'] == '00' && isset($res['accountinfo'])) {
                            $accountInfo = $res['accountinfo'];
                            foreach ($accountInfo as $account) {
                                $availableBalance += ($account['d0can_use'] + $account['t1can_use']) / 100;
                            }
                        }
                    } elseif ($payOutType == 17) {
                        if (isset($res['ret_Code']) && $res['ret_Code'] == 1 && isset($res['valid_Amount'])) {
                            $availableBalance = $res['valid_Amount'];
                        }
                    } elseif ($payOutType == 18) {
                        if (isset($res['field039']) && $res['field039'] == '00' && isset($res['field055'])) {
                            $availableBalance = $res['field055'] / 100;
                        }
                    } elseif ($payOutType == 19) {
                        if (isset($res['code']) && $res['code'] == '00' && isset($res['amount'])) {
                            $availableBalance = $res['amount'];
                        }
                    } elseif ($payOutType == 20) {
                        if (isset($res['success']) && $res['success'] == 1 && isset($res['balance'])) {
                            $amountarr        = json_decode($res['balance'], true);
                            $availableBalance = $amountarr['availableBalance'] / 100;
                        }
                    } elseif ($payOutType == 21) {
                        if (isset($res['field039']) && $res['field039'] == '00' && isset($res['field055'])) {
                            $availableBalance = $res['field055'] / 100;
                        }
                    } elseif ($payOutType == 22) {
                        if (isset($res['field039']) && $res['field039'] == '00' && isset($res['field055'])) {
                            $availableBalance = $res['field055'] / 100;
                        }
                    } elseif ($payOutType == 23) {
                        if (isset($res['status']) && $res['status'] == 'M0000' && isset($res['merBalance'])) {
                            $availableBalance = $res['merBalance'];
                        }
                    } elseif ($payOutType == 24) {
                        if (isset($res['code']) && isset($res['data']['availableAmountSum'])) {
                            $availableBalance = ($res['data']['availableAmountSum'] / 100) - ($res['data']['freezeAmountSum'] / 100);
                        }
                    } elseif ($payOutType == 25) {
                        if (isset($res['code']) && isset($res['balance'])) {
                            $availableBalance = ($res['balance']);
                        }
                    } elseif ($payOutType == 26) {
                        if (isset($res['RSPCOD']) && isset($res['balance'])) {
                            $availableBalance = ($res['balance']);
                        }
                    } elseif ($payOutType == 27) {
                        if (isset($res['state']) && isset($res['money'])) {
                            $availableBalance = $res['money'];
                        }
                    } elseif ($payOutType == 28) {
                        if (isset($res['fxstatus']) && isset($res['fxmoney'])) {
                            $availableBalance = $res['fxmoney'];
                        }
                    } elseif ($payOutType == 29) {
                        $res = json_decode($res,true);
                        if (isset($res['amount'])) {
                            $availableBalance = $res['amount'];
                        }
                    } elseif ($payOutType == 30) {
                        if ($res['ret_code'] == 0000 && $res['ret_msg'] == 'SUCCESS') {
                            $availableBalance = $res['t_availablebalance'];
                        }
                    }
                    $platform->balance = $availableBalance;
                    if ($platform->save()) {
                        $result['status'] = 0;
                        $result['msg']    = '余额查询成功';
                        $result['data']   = [
                            'balance'  => sprintf("%.2f", $availableBalance),
                            'original' => $res,
                        ];
                    }
                }
            }
        }

        return $response->withJson($result);
    }

    /**
     * @api {get} [/admin]/platformRecharge[/:token|?type=:type&companyId=:companyId&platformId=:platformId] 前后台出款平台充值表单
     * @apiName PlatformRecharge
     * @apiGroup Platform
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} type 出款平台类型 1、天付宝 2、雅付 3、金海哲 4、泽圣
     * @apiParam {Number} companyId 业务平台ID
     * @apiParam {Number} platformId 出款平台ID
     * @apiParam {String} token 出款充值URL链接参数
     *
     * @apiSuccessExample {html} Success-Response:
     *   HTTP/1.1 200 OK
     */
    public function platformRecharge(Request $request, Response $response, $args)
    {
        $flag = false;
        // 后台充值参数
        $rechargeLinkId = 0;
        $submitUrl      = '/admin/recharge';
        $getDatas       = $request->getQueryParams();
        $type           = $getDatas['type'] ?? 0;
        $companyId      = $getDatas['companyId'] ?? 0;
        $platformId     = $getDatas['platformId'] ?? 0;
        // 前台充值参数
        $token = $args['token'] ?? '';
        if (empty($getDatas) && $token) {
            $rechargeLink = RechargeLink::where('token', '=', $token)->where('status', '=', 0)->first();
            if ($rechargeLink) {
                $rechargeLinkId = $rechargeLink->id;
                $platform       = $rechargeLink->platform;
                $type           = $platform->pay_out_type;
                $companyId      = $platform->company_id;
                $platformId     = $platform->id;
                $flag           = true;
                $submitUrl      = '/recharge';
            }
        } elseif ($getDatas && empty($token)) {
            $flag = true;
        }

        $banks = $this->getPayBanksByType($type);

        $tmp = <<<EOT
<!DOCTYPE html>
<html lang="zh_cn">
<head>
    <meta charset="UTF-8">
    <title>出款平台充值</title>
    <style>
        body{
            background: #ececec;
            text-align: center;
        }
        .content{
            margin: 150px auto;
            width: 600px;
EOT;
        if ($flag) {
            $tmp .= 'height: 300px;';
        }
        $tmp .= <<<EOT
            background: #fff;
            padding: 10px;
            box-shadow: 0 2px 2px 0 rgba(0,0,0,.14), 0 1px 5px 0 rgba(0,0,0,.12), 0 3px 1px -2px rgba(0,0,0,.2);
        }
        form{
            margin-top: 60px;
        }
        input[type=text],select{
            width: 150px;
            border: 1px solid #ddd;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            border-radius: 5px;
            height: 30px;
            color: #666;
            padding: 0 5px;
        }
        label{
            display: block;
            line-height: 40px;
            margin-bottom: 10px;
            text-align: left;
            padding-left: 180px;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-image: none;
            border: 1px solid transparent;
            border-radius: 4px;
            box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14), 0 1px 5px 0 rgba(0,0,0,0.12), 0 3px 1px -2px rgba(0,0,0,0.2);
        }
        .btn.active.focus,.btn.active:focus,.btn.focus,.btn:active.focus,.btn:active:focus,.btn:focus {
            outline: thin dotted;
            outline: 5px auto -webkit-focus-ring-color;
            outline-offset: -2px
        }
        .btn.focus,.btn:focus,.btn:hover {
            color: #333;
            text-decoration: none;
            box-shadow: 0 3px 3px 0 rgba(0,0,0,0.14),0 1px 7px 0 rgba(0,0,0,0.12),0 3px 1px -1px rgba(0,0,0,0.2)
        }
        .btn.active,.btn:active {
            background-image: none;
            outline: 0;
            -webkit-box-shadow: inset 0 3px 5px rgba(0,0,0,.125);
            box-shadow: inset 0 3px 5px rgba(0,0,0,.125)
        }
        .btn.disabled,.btn[disabled],fieldset[disabled] .btn {
            pointer-events: none;
            cursor: not-allowed;
            filter: alpha(opacity=65);
            -webkit-box-shadow: none;
            box-shadow: none;
            opacity: .65
        }
        .btn-green {
            color: #fff;
            background-color: #5cb85c;
            border-color: #4cae4c
        }
        .btn-green.active,.btn-green.focus,.btn-green:active,.btn-green:focus,.btn-green:hover,.open>.dropdown-toggle.btn-green {
            color: #fff;
            background-color: #449d44;
            border-color: #398439
        }
        .btn-green.active,.btn-green:active,.open>.dropdown-toggle.btn-green {
            background-image: none
        }
        .btn-green.disabled,.btn-green.disabled.active,.btn-green.disabled.focus,.btn-green.disabled:active,.btn-green.disabled:focus,.btn-green.disabled:hover,.btn-green[disabled],.btn-green[disabled].active,.btn-green[disabled].focus,.btn-green[disabled]:active,.btn-green[disabled]:focus,.btn-green[disabled]:hover,fieldset[disabled] .btn-green,fieldset[disabled] .btn-green.active,fieldset[disabled] .btn-green.focus,fieldset[disabled] .btn-green:active,fieldset[disabled] .btn-green:focus,fieldset[disabled] .btn-green:hover {
            background-color: #5cb85c;
            border-color: #4cae4c
        }
        .btn-green .badge {
            color: #5cb85c;
            background-color: #fff
        }
    </style>
</head>
<body>
<div class="content">
EOT;
        if ($flag) {
            $tmp .= <<<EOT
    <h2>出款平台充值</h2>
    <form action="{$submitUrl}" method="POST" id="rechargeForm" class="rechargeForm">
        <input type="hidden" name="type" value="{$type}">
        <input type="hidden" name="companyId" value="{$companyId}">
        <input type="hidden" name="platformId" value="{$platformId}">
        <input type="hidden" name="rechargeLinkId" value="{$rechargeLinkId}">
        <label>选择银行：
            <select name="bank" style="width: 162px">
EOT;

            if ($banks) {
                foreach ($banks as $code => $bank) {
                    $tmp .= '<option value="' . $code . '">' . $bank . '</option>';
                }
            }

            $tmp .= <<<EOT
            </select>
        </label>
        <label>充值金额：
            <input type="text" name="amount">
        </label>
        <input type="submit" value="充值" onclick="return check(this.form)" class="btn btn-green" />
    </form>
    <script>
        function check(form) {
            if(form.amount.value==''|| !/^\d{1,7}$/.test(form.amount.value) || form.amount.value=="0") {
                alert("请输入正确的金额!");
                form.amount.focus();
                return false;
            }
            return true;
        }
    </script>
EOT;
        } else {
            $tmp .= '<p>充值链接地址不正确，请联系管理员获取正确的充值链接地址。</p>';
        }
        $tmp .= <<<EOT
</div>
</body>
</html>
EOT;

        $response->getBody()->write($tmp);

        return $response;
    }

    private function getPayBanksByType($type)
    {
        $res = [];
        if ($type) {
            if ($type == 1) {
                $res = Tianfubao::getInstance()->getPayType();
            } elseif ($type == 2) {
                $res = YafuNew::getInstance()->getPayType();
            } elseif ($type == 3) {
                $res = JinhaizheNew::getInstance()->getPayType();
            } elseif ($type == 4) {
                $res = Zesheng::getInstance()->getPayType();
            } elseif ($type == 6) {
                // $res = Kailiantong::getInstance()->getPayType();
            } elseif ($type == 8) {
                $res = Shangma::getInstance()->getPayType();
            } elseif ($type == 9) {
                $res = Xifu::getInstance()->getPayType();
            } elseif ($type == 10) {
                $res = Aifu::getInstance()->getPayType();
            } elseif ($type == 11) {
                $res = Nongfu::getInstance()->getPayType();
            } elseif ($type == 12) {
                $res = Shunxin::getInstance()->getPayType();
            } elseif ($type == 13) {
                $res = Tianji::getInstance()->getPayType();
            } elseif ($type == 14) {
                $res = Xunjie::getInstance()->getPayType();
            } elseif ($type == 15) {
                $res = Duobao::getInstance()->getPayType();
            } elseif ($type == 16) {
                $res = Bingo::getInstance()->getPayType();
            } elseif ($type == 20) {
                $res = Gaiya::getInstance()->getPayType();
            } elseif ($type == 21) {
                $res = Qingying::getInstance()->getPayType();
            } elseif ($type == 22) {
                $res = Jiyun::getInstance()->getPayType();
            } elseif ($type == 23) {
                $res = RHPay::getInstance()->getPayType();
            } elseif ($type == 24) {
                $res = Gft::getInstance()->getPayType();
            } elseif ($type == 25) {
                $res = AgencyZhongxin::getInstance()->getPayType();
            } elseif ($type == 26) {
                $res = AgencyGPpay::getInstance()->getPayType();
            } elseif ($type == 27) {
                $res = AgencyXianfeng::getInstance()->getPayType();
            } elseif ($type == 28) {
                $res = AgencyTongfu::getInstance()->getPayType();
            } elseif ($type == 29) {
                $res = AgencySDpay::getInstance()->getPayType();
            }
        }
        return $res[3] ?? ['' => '暂未对接充值接口'];
    }
}
