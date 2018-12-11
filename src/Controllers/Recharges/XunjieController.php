<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Xunjie;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class XunjieController extends BaseController
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
        //     "respDesc": "交易成功",
        //     "orderNo": "201804031550235675023",
        //     "merNo": "850510059375264",
        //     "productId": "0001",
        //     "transType": "SALES",
        //     "serialId": "O270001180208",
        //     "signature": "ctu6m9hGSxxzTzStzh5pihnTfRJv2lYzoj46MMXxCne5lgmZVoAIdK6bDeDxHvcQAmIBoDAQmCrJHwqJNQfWOUM2Q562a6l/cfPPPas/Mfh+TwJDZdZlqYR8KuZ2Pkj0PoHYNt7liFJDL06BmHeJyY7CZxZKIUEKQ11IoHcd7eu9KYwOaocMHX2L7ZbR9mC0UpgjdkMC0wu85qKDJaPnHMEGaiFaRW4/SjBttYAJ1d4102Km/kuQLuVXZTLr11MCYda4IVOTPJYqpnogYwamSkOpSPdRVozCvDxyf2tX/wrnDSPA4P+8H9vejediVGjIJeAnhlb9ZCGExDLKdoevRA==",
        //     "transAmt": "1",
        //     "orderDate": "20180403",
        //     "respCode": "0000"
        // }
        //日志
        $this->logger->addInfo('Xunjie recharge callback data:', $returnData);

        if (isset($returnData['orderNo']) && $returnData['orderNo']) {
            $order_no = $returnData['orderNo']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['transAmt'] / 100); // 金额
            $state    = $returnData['respCode'] ?? ''; // 支付状态

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
                            $verify              = Xunjie::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == '0000') {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $returnData['serialId'];
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

        $this->logger->addInfo('Xunjie recharge notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
