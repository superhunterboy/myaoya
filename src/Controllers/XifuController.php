<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Xifu;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class XifuController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        /*回掉
        {
            "gmt_create":"2018-01-26 17:02:43",
            "order_no":"201801261702423870243",
            "gmt_payment":"2018-01-26 17:02:43",
            "seller_email":"171991360@qq.com",
            "notify_time":"2018-01-26 17:02:43",
            "quantity":"1",
            "sign":"AA43FC421414BE2622ADEE498BAB9FB0720A7695",
            "discount":"0.00",
            "body":"ready",
            "is_success":"T",
            "title":"sunny",
            "gmt_logistics_modify":"2018-01-26 17:02:43",
            "notify_id":"76ec7e162c1447a2bede2aaf9e973058",
            "notify_type":"WAIT_TRIGGER",
            "payment_type":"1",
            "ext_param2":"WXPAY",
            "price":"0.01",
            "total_fee":"0.01",
            "trade_status":"TRADE_FINISHED",
            "trade_no":"101801261343550",
            "signType":"SHA",
            "seller_actions":"SEND_GOODS",
            "seller_id":"100000000002565",
            "is_total_fee_adjust":"0"
        }
        */

        //日志
        $this->logger->addInfo('Xifu payment callback data:', $returnData);

        $returnMsg = '';
        if (isset($returnData['order_no']) && $returnData['order_no']) {

            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['total_fee'] ?? 0; //金额
            $state    = $returnData['trade_status'] ?? ''; //支付状态
            $orderMer = $returnData['trade_no'] ?? date('YmdHis'); //流水号

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == 'TRADE_FINISHED') {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {
                    if ($pay->vendor_id && $pay->company_id) {
                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify   = false;
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 39) {
                                $verify = Xifu::getInstance($conf)->verifySign($returnData);
                                if ($verify) {
                                    $status = 0;

                                    $pay->vendor_order_no = $orderMer;
                                    $pay->status          = $status;
                                    $pay->pay_datetime    = date('Y-m-d H:i:s');
                                    $pay->remark          = json_encode($returnData);

                                    $res = $pay->save();

                                    if ($res) {
                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => date('Y-m-d H:i:s'),
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 记录日志
                                                $this->logger->addInfo('Xifu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'success';
                                    }
                                }
                            }
                        }
                    }
                } else {

                    $returnMsg = '订单号错误';
                }

            }

        }
        return $response->getBody()->write($returnMsg);
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Xifu payment notify data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {

            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['total_fee'] ?? 0; //金额
            $state    = $returnData['trade_status'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == 'TRADE_FINISHED') {

                if ($pay->vendor_id && $pay->company_id) {

                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

            }
        }

        $response->getBody()->write('支付失败');

    }

    //查询通知
    public function query(Request $request, Response $response, $args)
    {

    }

}
