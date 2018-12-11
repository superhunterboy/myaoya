<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\YafuNew;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class YafuNewController extends BaseController
{
    /**
     * 新雅付支付回调
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

        /*{"consumerNo":"20781",
        "merOrderNo":"201709221606047980607",
        "merRemark":"201709221606047980607",
        "orderNo":"20170922160607783267",
        "orderStatus":"1",
        "payType":"0201",
        "sign":"D83B6E4557C5C0861FC8AFBAEC02C6AE",
        "transAmt":"0.01",
        "version":"3.0"
        }*/

        $this->logger->addInfo('YafuNew recharge callback data:', $returnData);

        if (isset($returnData['merOrderNo']) && $returnData['merOrderNo']) {
            $order_no = $returnData['merOrderNo'];
            $money    = $returnData['transAmt'];
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
                            $verify              = YafuNew::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($returnData['orderStatus'] == 1) {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $returnData['orderNo'];
                                $recharge->status          = $status;
                                $recharge->remark          = json_encode($returnData);
                                $res                       = $recharge->save();
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
     * 新雅付支付通知
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
        //     "merOrderNo": "201705241335086743510",
        //     "transAmt": "0.1",
        //     "orderStatus": "1",
        //     "orderNo": "20170524133510618905",
        //     "code": "",
        //     "sign": "DD596A752593771C74400B348B315A7C",
        //     "consumerNo": "20133"
        // }

        $this->logger->addInfo('YafuNew recharge notify data:', $returnData);

        $status = 1;

        if (isset($returnData['orderStatus']) && $returnData['orderStatus'] && $returnData['orderStatus'] == 1) {
            $status = 0;
        }

        if ($status == 0) {
            $order_no = '';
            if (isset($returnData['merOrderNo']) && $returnData['merOrderNo']) {
                $order_no = $returnData['merOrderNo'];
            }

            if ($order_no) {
                $recharge = Recharge::where('order_no', '=', $order_no)->first();
                if ($recharge) {
                    $response->getBody()->write('充值成功');
                }
            }
        } else {
            $response->getBody()->write('failure');
        }
        return $response;
    }
}
