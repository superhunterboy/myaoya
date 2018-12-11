<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Duoduo;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class DuoduoController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        //日志
        $this->logger->addInfo('Duoduo payment callback data:', $returnData);

        if (isset($returnData['OrdId']) && $returnData['OrdId']) {

            $order_no = $returnData['OrdId'];
            $money    = $returnData['OrdAmt'];

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = '';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 11) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Duoduo::getInstance($conf)->verifySign($returnData);

                                if ($verify) {

                                    $status = 1;

                                    if ($returnData['ResultCode'] === 'success002') {

                                        $status = 0;

                                    }

                                    $pay->vendor_order_no = $returnData['OrdNo'];
                                    $pay->status          = $status;
                                    $pay->pay_datetime    = date('Y-m-d H:i:s');
                                    $pay->remark          = json_encode($returnData);

                                    $res = $pay->save();

                                    if ($res) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => date('Y-m-d H:i:s'),
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 记录日志
                                                $this->logger->addInfo('Duoduo payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'success|9999';
                                    } else {

                                        $returnMsg = '数据读取失败';
                                    }
                                } else {

                                    $returnMsg = '签名错误';
                                }
                            } else {

                                $returnMsg = '平台错误';
                            }
                        } else {

                            $returnMsg = '平台异常';
                        }
                    } else {

                        $returnMsg = '接口异常';
                    }
                } else {

                    $returnMsg = '订单号错误';
                }

            }

            $response->getBody()->write($returnMsg);
        }
        return $response;
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();
        }

        /**
         *{
         * ["MerId"]=>
         * string(8) "90168083"
         * ["OrdId"]=>
         * string(21) "201706201406093050716"
         * ["OrdAmt"]=>
         * string(4) "1.00"
         * ["OrdNo"]=>
         * string(26) "DTDD2017062014071633233442"
         * ["ResultCode"]=>
         * string(10) "success002"
         * ["Remark"]=>
         * string(21) "201706201406093050716"
         * ["SignType"]=>
         * string(3) "MD5"
         * ["SignInfo"]=>
         * string(32) "adaca31e3c732cb008223ff023471b9e"
         *}
         */

        // 日志
        $this->logger->addInfo('Duoduo payment notify data:', $returnData);

        if (isset($returnData['OrdId']) && $returnData['OrdId']) {

            $order_no = $returnData['OrdId'];
            $money    = $returnData['OrdAmt'];

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay) {

                if ($pay->vendor_id && $pay->company_id) {

                    $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                $bool = Utils::verifySignDuoduo($returnData, $vendor->key);

                if ($bool) {

                    if ($returnData['ResultCode'] == 'success002') {

                        return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');
                    } else {

                        $response->getBody()->write('支付失败');
                    }
                } else {

                    $response->getBody()->write('请求异常');
                }
            }
        } else {

            $response->getBody()->write('订单号或者金钱数量错误');
        }

    }

    //查询通知
    public function query(Request $request, Response $response, $args)
    {

    }

}
