<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\AgencyPayments\GPpay;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;
use \Weiming\Models\Withdrawal;

class GppayController extends BaseController
{
    /**
     * 支付异步回调
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
        /*回掉
        {
            "merchant_no": "xxx",
            "sign": "xxx",
                "orders": [
                    {
                        "order_no": "xxx",
                        "mer_order_no": "xxx",
                        "result": "xxx",
                        "amount": "xxx",
                        "withdraw_fee": "xxx"
                    }
                ]
        }
         */
        //日志
        $this->logger->addInfo('Gppay recharge notify data:', $returnData);

        if (isset($returnData['orders'][0]['order_no']) && $returnData['orders'][0]['order_no']) {
            $merchant_no             = $returnData['merchant_no']; //商户号
            $signData                = $returnData['orders'][0];
            $order_no                = $signData['order_no']; //上传订单号
            $orderMer                = $signData['mer_order_no'] ?? date('YmdHis'); //商户订单号
            $state                   = $signData['result'] ?? 'F'; //支付状态
            $money                   = $signData['amount'] ?? 0; //金额
            $withdraw_fee            = $signData['withdraw_fee'] ?? 0; //代付手续费
            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                $recharge = Withdrawal::where('order_no', '=', $order_no)->where('amount', '=', sprintf("%.2f", $money))->first();
                if ($recharge) {
                    if ($recharge->platform_id) {
                        $platform = Platform::where('id', '=', $recharge->platform_id)->first();
                        if ($platform) {
                            $conf['parterNo']    = $platform->no;
                            $conf['parterKey']   = $platform->key;
                            $conf['callbackUrl'] = $platform->callback_url;
                            $conf['notifyUrl']   = $platform->notify_url;
                            $verify              = false;
                            $returnMsg           = 'fail';
                            $verify              = GPpay::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 0;
                                if ($state == 'S') {
                                    $status = 1;
                                }
                                $recharge->platform_order_no = $orderMer;
                                $recharge->status            = $status;
                                $recharge->remark            = json_encode($returnData);
                                $res                         = $recharge->save();
                                if ($res) {
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = 'success';
                                    $response->getBody()->write($returnMsg);
                                    return $response->withStatus(200);
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response->withStatus(500);
    }

    /**
     * 同步通知，没有带参数，支付成功后同步通知
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

        // 日志
        $this->logger->addInfo('Gppay recharge callback data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {
            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['amount'] ?? 0; //金额
            $state    = $returnData['result'] ?? 'F'; //支付状态
            $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
            if ($recharge && $state == 'S') {
                return $response->getBody()->write('充值成功');
            }
        }
        return $response->getBody()->write('支付失败');
    }
}
