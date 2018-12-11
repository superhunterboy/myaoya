<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Xifu;
use \Weiming\Models\Company;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class XifuController extends BaseController
{
    /**
     * 支付异步回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
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
        $this->logger->addInfo('Xifu recharge callback data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {
            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['total_fee'] ?? 0; //金额
            $state    = $returnData['trade_status'] ?? ''; //支付状态
            $orderMer = $returnData['trade_no'] ?? date('YmdHis'); //流水号
            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
                if ($recharge) {
                    if ($recharge->platform_id) {
                        $platform = Platform::where('id', '=', $recharge->platform_id)->first();
                        if ($platform) {
                            $conf['parterNo']    = $platform->no;
                            $conf['parterKey']   = $platform->key;
                            $conf['callbackUrl'] = $platform->callback_url;
                            $conf['notifyUrl']   = $platform->notify_url;
                            $verify              = false;
                            $returnMsg           = '';
                            $verify              = Xifu::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 'TRADE_FINISHED') {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $orderMer;
                                $recharge->status          = $status;
                                $recharge->remark          = json_encode($returnData);
                                $res                       = $recharge->save();
                                if ($res) {
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = 'success';
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 同步通知，没有带参数，支付成功后同步通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function notify(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Xifu recharge notify data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {
            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['total_fee'] ?? 0; //金额
            $state    = $returnData['trade_status'] ?? ''; //支付状态
            $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
            if ($recharge && $state == 'TRADE_FINISHED') {
                return $response->getBody()->write('充值成功');
            }
        }
        return $response->getBody()->write('支付失败');
    }
}
