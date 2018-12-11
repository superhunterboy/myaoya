<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\JinhaizheNew;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

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
        $jsonArr = $request->getQueryParams();

        if ($request->isPost()) {

            $jsonArr = $request->getParsedBody();

        }

        $retArr = json_decode($jsonArr['ret'], true);
        $msgArr = json_decode($jsonArr['msg'], true);

        $order_no = $msgArr['no'];
        $money    = sprintf("%.2f", $msgArr['money'] / 100);

        // 微信
        // {
        //     "sign": "Z/KNDcz+KX7HJ5RpCpJJRtoEzjkzeTvY1RsqMa/oV+sO4/Nwjr84Ndo16rmntdrtBf5RpIlQQipuJPZijDJ6l7cgJqnOMJMHfaBofxhoFAjRfFJ7/Yo98lioUqaCjN5Vs0Yz3K+QuP//jRmc5d/1ABNHjRfQVSnDZDV7ZV01BAM=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":1,\"orderDate\":\"2017-03-24 09:57:37\",\"no\":\"201703240956555425639\",\"payNo\":\"a6719c2b10a0484090eaa3b7d5caeb08\",\"remarks\":\"201703240956555425639\"}"
        // }

        // 支付宝
        // {
        //     "sign": "bXpbqRStmV3yVaev9/wEBfE+rMunBVRUWrcimR4ezzJCgvGYF/5fpUOmm0+FNaM52q+L9XBWefWoSGzH6CuS8DbrAzz4AWQvJamvgSROMnnrbMdv4zN5YIXSiinRXX3bRKvRrwjW36wmDbi15CoiPoqJOAUt3/a5OgIaI1fMQ5Y=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":1,\"orderDate\":\"2017-03-24 10:02:26\",\"no\":\"201703241001356260119\",\"payNo\":\"bbaedf3105414a55a266464a8d4fd126\",\"remarks\":\"201703241001356260119\"}"
        // }

        // 网银
        // {
        //     "sign": "IozqmouEFStlpBLhDxQGSSWdU8zkGctCBXlHXfBT/6p7p16lQ2YVVpIykzcSWN6R6FGirfj5FE//1WHWAtfsLlHDzXOXMIQrKiLG7wsRbWfMUw5V+Hi6QgzXvouhqsZMKTXd9XgJjCl2eKj2Pcz9m9qcqM1fwFQIc1s/4ScVjS0=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":30,\"orderDate\":\"2017-03-24 10:14:28\",\"no\":\"201703241012472351231\",\"payNo\":\"99af71426f404db89e5a36f0c8265df2\",\"remarks\":\"201703241012472351231\"}"
        // }

        $this->logger->addInfo('JinhaizheNew payment callback data:', $jsonArr);

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = 'FAIL';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 12) {

                                $verify = JinhaizheNew::getInstance($conf)->verifySign($jsonArr);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($retArr['code'] == '1000' && $retArr['msg'] == 'SUCCESS') {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $msgArr['payNo'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = $msgArr['orderDate'];
                                $pay->remark          = json_encode($jsonArr);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 12) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => $msgArr['orderDate'],
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 记录日志
                                                $this->logger->addInfo('JinhaizheNew payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'SUCCESS';

                                    }
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

        $this->logger->addInfo('JinhaizheNew payment notify data:', $getDatas);

        return $response;
    }

}
