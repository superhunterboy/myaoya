<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\JinhaizheNew;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class JinhaizheNewController extends BaseController
{
    /**
     * 金海哲(新)支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function callback(Request $request, Response $response, $args)
    {
        // 网银
        // {
        //     "sign": "IozqmouEFStlpBLhDxQGSSWdU8zkGctCBXlHXfBT/6p7p16lQ2YVVpIykzcSWN6R6FGirfj5FE//1WHWAtfsLlHDzXOXMIQrKiLG7wsRbWfMUw5V+Hi6QgzXvouhqsZMKTXd9XgJjCl2eKj2Pcz9m9qcqM1fwFQIc1s/4ScVjS0=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":30,\"orderDate\":\"2017-03-24 10:14:28\",\"no\":\"201703241012472351231\",\"payNo\":\"99af71426f404db89e5a36f0c8265df2\",\"remarks\":\"201703241012472351231\"}"
        // }

        $jsonArr = $request->getQueryParams();
        if ($request->isPost()) {
            $jsonArr = $request->getParsedBody();
        }

        $retArr = json_decode($jsonArr['ret'], true);
        $msgArr = json_decode($jsonArr['msg'], true);

        $order_no = $msgArr['no'];
        $money    = sprintf("%.2f", $msgArr['money'] / 100);

        $this->logger->addInfo('JinhaizheNew recharge callback data:', $jsonArr);

        if ($order_no) {
            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
                if ($recharge) {
                    if ($recharge->platform_id) {
                        $platform = Platform::where('id', '=', $recharge->platform_id)->first();
                        if ($platform) {
                            $conf = [
                                'parterNo'    => $platform->no,
                                'parterKey'   => $platform->key,
                                'callbackUrl' => $platform->callback_url,
                                'notifyUrl'   => $platform->notify_url,
                            ];
                            $verify    = false;
                            $returnMsg = 'FAIL';
                            $verify    = JinhaizheNew::getInstance($conf)->verifySign($jsonArr);
                            if ($verify) {
                                $status = 1;
                                if ($retArr['code'] == '1000' && $retArr['msg'] == 'SUCCESS') {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $msgArr['payNo'];
                                $recharge->status            = $status;
                                $recharge->remark            = json_encode($jsonArr);
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
     * 金海哲(新)支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function notify(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        $this->logger->addInfo('JinhaizheNew recharge notify data:', $getDatas);

        return $response;
    }

}
