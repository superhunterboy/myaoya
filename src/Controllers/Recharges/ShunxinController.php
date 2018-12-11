<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Shunxin;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class ShunxinController extends BaseController
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
        $this->logger->addInfo('Shunxin recharge callback data:', $returnData);

        if (isset($returnData['orderNo']) && $returnData['orderNo']) {
            $order_no   = $returnData['orderNo']; //上传订单号
            $money      = sprintf("%.2f", $returnData['tradeAmt']); //金额
            $state      = $returnData['orderStatus'] ?? 2; //支付状态
            $notifyType = $returnData['notifyType'];
            if ($notifyType == 0) {
                // 前台通知
                return $response->getBody()->write('充值成功');
            }
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
                            $returnMsg           = 'FAILURE';
                            $verify              = Shunxin::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 1) {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $returnData['accNo'];
                                $recharge->status            = $status;
                                $recharge->remark            = json_encode($returnData);
                                $res                         = $recharge->save();
                                if ($res) {
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = 'SUCCESS';
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

        $this->logger->addInfo('Shunxin recharge notify data:', $returnData);

        return $response;
    }
}
