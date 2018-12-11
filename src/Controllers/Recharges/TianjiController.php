<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Tianji;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class TianjiController extends BaseController
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
            $returnData = file_get_contents('php://input');
            $returnData = json_decode($returnData, true);
        }

        // {
        //     "merchant_id": "100038",
        //     "order_id": "201803141049045774904",
        //     "trans_amt": 100,
        //     "send_time": "20180314104904",
        //     "resp_desc": "交易成功",
        //     "resp_code": "0000",
        //     "sign": "f6c130769b59a218f95c9e524d1bd9b8"
        // }

        //日志
        $this->logger->addInfo('Tianji recharge callback data:', $returnData);

        if (isset($returnData['order_id']) && $returnData['order_id']) {
            $order_no   = $returnData['order_id']; //上传订单号
            $money      = sprintf("%.2f", $returnData['trans_amt'] / 100); //金额
            $state      = $returnData['resp_code'] ?? ''; //支付状态

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
                            $verify              = Tianji::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == '0000') {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $returnData['order_id'];
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
            $returnData = file_get_contents('php://input');
            $returnData = json_decode($returnData, true);
        }

        $this->logger->addInfo('Tianji recharge notify data:', $returnData);

        return $response;
    }
}
