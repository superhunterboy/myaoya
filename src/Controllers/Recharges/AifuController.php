<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Aifu;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class AifuController extends BaseController
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
        "merchant_no":"144801006875",
        "order_no":"201801262133004083302",
        "order_amount":"0.01",
        "original_amount":"0.01",
        "upstream_settle":"0",
        "result":"S",
        "pay_time":"20180126213400",
        "trace_id":"2554956",
        "reserve":"shop",
        "sign":"76bfbd78c4931105b377436a611a33ba"
        }
         */

        //日志
        $this->logger->addInfo('Aifu recharge callback data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {
            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['original_amount'] ?? 0; //金额
            $state    = $returnData['result'] ?? 'F'; //支付状态
            $orderMer = $returnData['trace_id'] ?? date('YmdHis'); //流水号
            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
                if ($recharge) {
                    if ($recharge->platform_id) {
                        $platform = Platform::where('id', '=', $recharge->platform_id)->first();
                        if ($platform) {
                            $parterKey           = json_decode($platform->key, true);
                            $conf['parterNo']    = $platform->no;
                            $conf['parterKey']   = $parterKey['md5Key'];
                            $conf['callbackUrl'] = $platform->callback_url;
                            $conf['notifyUrl']   = $platform->notify_url;
                            $verify              = false;
                            $returnMsg           = 'fail';
                            $verify              = Aifu::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 'S') {
                                    $status = 0;
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
    public function notify(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Aifu recharge notify data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {
            $order_no = $returnData['order_no']; //上传订单号
            $money    = $returnData['original_amount'] ?? 0; //金额
            $state    = $returnData['result'] ?? 'F'; //支付状态
            $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
            if ($recharge && $state == 'S') {
                return $response->getBody()->write('充值成功');
            }
        }
        return $response->getBody()->write('支付失败');
    }
}
