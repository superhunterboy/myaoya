<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Bingo;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class BingoController extends BaseController
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
        // {
        //     "amount":"1000",
        //     "goods_info":"CZ",
        //     "merno":"312018042223067147",
        //     "order_id":"201806271651089682316",
        //     "orgid":"2220180022230663",
        //     "plat_order_id":"2018062716510888173426",
        //     "sign_data":"462f832861b062aa9d98b350753f3753",
        //     "timestamp":"20180627165942",
        //     "trade_date":"2018-06-27 16:59:42",
        //     "trade_status":"0"
        // }
        //日志
        $this->logger->addInfo('Bingo recharge callback data:', $returnData);

        if (isset($returnData['order_id']) && $returnData['order_id']) {
            $order_no = $returnData['order_id']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['amount'] / 100); // 金额
            $state    = $returnData['trade_status'] ?? ''; // 支付状态

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
                            $returnMsg           = '{"responseCode": "9999"}';
                            $verify              = Bingo::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 0) {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $returnData['plat_order_id'];
                                $recharge->status            = $status;
                                $recharge->remark            = json_encode($returnData);
                                $res                         = $recharge->save();
                                if ($res) {
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = '{"responseCode": "0000"}';
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
        // {
        //     "amount":"1000",
        //     "goods_info":"CZ",
        //     "merno":"312018042223067147",
        //     "order_id":"201806271651089682316",
        //     "orgid":"be888eb705d249078ac5965e410a1c9d",
        //     "plat_order_id":"2018062716510888173426",
        //     "sign_data":"89dea40a09b0c63300b258a66b86e9e9",
        //     "timestamp":"20180627170008",
        //     "trade_date":"2018-06-27 16:59:42",
        //     "trade_status":"0"
        // }
        $this->logger->addInfo('Bingo recharge notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
