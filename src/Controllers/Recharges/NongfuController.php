<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Nongfu;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class NongfuController extends BaseController
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

        //日志
        $this->logger->addInfo('Nongfu recharge callback data:', $returnData);

        if (isset($returnData['merOrderId']) && $returnData['merOrderId']) {
            $order_no = $returnData['merOrderId']; //上传订单号
            $money    = sprintf("%.2f", ($returnData['txnAmt'] / 100)); //金额
            $state    = $returnData['respCode'] ?? '1002'; //支付状态
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
                            $returnMsg           = 'failure';
                            $verify              = Nongfu::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == '1001') {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = 'WM' . $order_no;
                                $recharge->status            = $status;
                                $recharge->remark            = json_encode($returnData);
                                $res                         = $recharge->save();
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
        $this->logger->addInfo('Nongfu recharge notify data:', $returnData);

        if (isset($returnData['merOrderId']) && $returnData['merOrderId']) {
            $order_no = $returnData['merOrderId']; //上传订单号
            $money    = sprintf("%.2f", ($returnData['txnAmt'] / 100)); //金额
            $state    = $returnData['respCode'] ?? '1002'; //支付状态
            $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
            if ($recharge && $state == '1001') {
                return $response->getBody()->write('充值成功');
            }
        }
        return $response->getBody()->write('支付失败');
    }
}
