<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Zesheng;
use \Weiming\Models\Company;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class ZeshengController extends BaseController
{
    /**
     * 异步回调
     */
    public function callback(Request $request, Response $response, $args)
    {
        /**
         *   {
         *       "ext":"",
         *       "totalAmount":"1",
         *       "merchantCode":"1000002005",
         *       "transTime":"20171023152436",
         *       "transType":"00202",
         *       "instructCode":"2017102300024532442",
         *       "outOrderId":"201710231523035802303",
         *       "sign":"A6F4BE84407F3771EC2E6F37F872219C"
         *   }
         */
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }
        $this->logger->addInfo('Zesheng recharge callback data:', $returnData);
        if (isset($returnData['outOrderId']) && $returnData['outOrderId']) {
            $returnMsg = '';
            $order_no  = $returnData['outOrderId'];
            $money     = sprintf("%.2f", $returnData['totalAmount'] / 100);
            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
                if ($recharge) {
                    if ($recharge->platform_id) {
                        $platform = Platform::where('id', '=', $recharge->platform_id)->first();
                        if ($platform) {
                            $verify = false;
                            $conf   = [
                                'parterNo'    => $platform->no,
                                'parterKey'   => $platform->key,
                                'callbackUrl' => $platform->callback_url,
                                'notifyUrl'   => $platform->notify_url,
                            ];
                            $verify = Zesheng::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $recharge->platform_order_no = $returnData['instructCode'];
                                $recharge->status            = 0;
                                $recharge->remark            = json_encode($returnData);
                                $res                         = $recharge->save();
                                if ($res) {
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = '{"code":"00"}';
                                }
                            }
                        }
                    }
                }
            }
            $response->getBody()->write($returnMsg);
        }
        return $response;
    }

    /**
     * 同步通知
     */
    public function notify(Request $request, Response $response, $args)
    {
        /**
         *   {
         *       "ext":"",
         *       "totalAmount":"1",
         *       "merchantCode":"1000002005",
         *       "transTime":"20171023152436",
         *       "transType":"00202",
         *       "instructCode":"2017102300024532442",
         *       "outOrderId":"201710231523035802303",
         *       "sign":"A6F4BE84407F3771EC2E6F37F872219C"
         *   }
         */
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }
        $this->logger->addInfo('Zesheng recharge notify data:', $returnData);
        if (isset($returnData['outOrderId']) && $returnData['outOrderId']) {
            $order_no = $returnData['outOrderId'];
            $money    = $returnData['totalAmount'] / 100;
            $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
            if ($recharge) {
                $response->getBody()->write('充值成功');
            }
        }
        return $response;
    }
}
