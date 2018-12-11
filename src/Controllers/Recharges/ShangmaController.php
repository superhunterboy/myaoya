<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Shangma;
use \Weiming\Models\Company;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class ShangmaController extends BaseController
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
        "amount":"2.0",
        "outTradeNo":"201712131816213991621",
        "remark":"smart",
        "resultCode":"00",
        "resultMsg":"操作成功",
        "returnCode":"2",
        "sign":"FBd39fXO8cEsToDgHdaO+Od+IEQht6MhOdXaOVxTU2wxAF9XntPgBPj6dDC0r5ulUx8a3AI56pJOzhV/kFgJBIye1MuDl5+DumgGljTnaSc2A0a8xU6m8kRqADk/liD1MjI4sJzr1mFUCOXyj6q7U5WIdd1sZdXPW8D5tCHzYiF1LiVx73DXJKBWdNmP/vAZGbsB5H7l4dTBnuNtGyEYnyneD1LAvB9SpDv35zKe7dpMSgNpyg2dRsM0IH04VE4y0HrSTI2x0bVbCWShIqUW+USXh2igHNDA6mEAb/aEGj5zqexDVO6WPiuVx76BEqS9fQFvjDCUCSRJLmK4WcWg+A=="
        }
         */

        //日志
        $this->logger->addInfo('Shangma recharge callback data:', $returnData);

        if (isset($returnData['outTradeNo']) && $returnData['outTradeNo']) {
            $order_no = $returnData['outTradeNo'];
            $money    = $returnData['amount'] / 100 ?? 0; //金额
            $money    = sprintf("%.2f", $money); //格式化金额
            $state    = $returnData['returnCode'] ?? ''; //支付状态
            $order_me = $returnData['outTradeNo'] ?? ''; //支付平台流水号
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
                            $verify              = Shangma::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 2) {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $order_me;
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
        $this->logger->addInfo('Shangma recharge notify data:', $returnData);

        if (isset($returnData['outTradeNo']) && $returnData['outTradeNo']) {
            $order_no = $returnData['outTradeNo'];
            $money    = $returnData['amount'] / 100 ?? 0; //金额
            $money    = sprintf("%.2f", $money); //格式化金额
            $state    = $returnData['returnCode'] ?? ''; //支付状态
            $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
            if ($recharge && $state == 2) {
                return $response->getBody()->write('充值成功');
            }
        }
        return $response->getBody()->write('支付失败');
    }
}
