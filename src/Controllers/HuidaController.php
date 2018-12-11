<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Huida;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class HuidaController extends BaseController
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
            "gmt_create":"2017-10-20 16:32:21",
            "order_no":"201710201632206223221",
            "gmt_payment":"2017-10-20 16:32:21",
            "seller_email":"huidaerwei@gmail.com",
            "notify_time":"2017-10-20 16:32:21",
            "quantity":"1",
            "sign":"700D0CAC0EF6974DC3452262F290B3633C5936B8",
            "discount":"0.00",
            "body":"201710201632206223221",
            "is_success":"T",
            "title":"201710201632206223221",
            "gmt_logistics_modify":"2017-10-20 16:32:21",
            "notify_id":"aa19d8444b7f467b98506a5418bb3280",
            "notify_type":"WAIT_TRIGGER",
            "payment_type":"1",
            "ext_param2":"WXPAY",
            "price":"0.01",
            "total_fee":"0.01",
            "trade_status":"TRADE_FINISHED",
            "trade_no":"101710200383685",
            "signType":"SHA",
            "seller_actions":"SEND_GOODS",
            "seller_id":"100000000002304",
            "is_total_fee_adjust":"0"
        }
        */

        //日志
        $this->logger->addInfo('Huida payment callback data:', $returnData);


        if (isset($returnData['order_no']) && $returnData['order_no']) {

            $order_no = $returnData['order_no'];
            $money    = $returnData['total_fee'];

            $returnMsg = '';

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 19) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Huida::getInstance($conf)->verifySign($returnData);

                                if ($verify) {

                                    $status = 1;

                                    if ($returnData['trade_status'] === 'TRADE_FINISHED') {

                                        $status = 0;

                                    }

                                    $pay->vendor_order_no = $returnData['trade_no'];
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
                                                $this->logger->addInfo('Huida payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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

            $response->getBody()->write($returnMsg);
        }
        return $response;
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();
        }

        /*回掉
        {
            "gmt_create":"2017-10-20 16:32:21",
            "order_no":"201710201632206223221",
            "gmt_payment":"2017-10-20 16:32:21",
            "seller_email":"huidaerwei@gmail.com",
            "notify_time":"2017-10-20 16:32:21",
            "quantity":"1",
            "sign":"700D0CAC0EF6974DC3452262F290B3633C5936B8",
            "discount":"0.00",
            "body":"201710201632206223221",
            "is_success":"T",
            "title":"201710201632206223221",
            "gmt_logistics_modify":"2017-10-20 16:32:21",
            "notify_id":"aa19d8444b7f467b98506a5418bb3280",
            "notify_type":"WAIT_TRIGGER",
            "payment_type":"1",
            "ext_param2":"WXPAY",
            "price":"0.01",
            "total_fee":"0.01",
            "trade_status":"TRADE_FINISHED",
            "trade_no":"101710200383685",
            "signType":"SHA",
            "seller_actions":"SEND_GOODS",
            "seller_id":"100000000002304",
            "is_total_fee_adjust":"0"
        }
        */

        // 日志
        $this->logger->addInfo('Huida payment notify data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {

            $order_no = $returnData['order_no'];
            $money    = $returnData['total_fee'];

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay) {

                if ($pay->vendor_id && $pay->company_id) {

                    $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                if ($returnData['trade_status'] == 'TRADE_FINISHED') {

                    return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');
                } else {

                    $response->getBody()->write('支付失败');
                }

            }
        }

        $response->getBody()->write('订单号或者金钱数量错误');

    }

    //查询通知
    public function query(Request $request, Response $response, $args)
    {

    }

}
