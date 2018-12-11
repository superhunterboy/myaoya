<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Wofu;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class WofuController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        //日志
        $this->logger->addInfo('Wofu payment callback data:', $returnData);
        /*回掉
        {   "trade_no":"1004070440",
            "sign_type":"RSA-S",
            "notify_type":"offline_notify",
            "merchant_code":"501502002027",
            "order_no":"201710101602419010243",
            "trade_status":"SUCCESS",
            "sign":"EX7ZgtMUUxyBv3sAXc4+BG9aILU82VSnarf4PZ95rbOqD6+HnUXmqIE8u9dRn1SI7nxLiOh07aMV1dmURXQqGZqMZpFhU3f+vHM/NPsN43XTw58e9iJ4j95GjSPnNqO+6zFnLSD09IQOlz+73OQs6zqPQ1/EH6H4DpwF1KaGpWk=",
            "order_amount":"0.01",
            "interface_version":"V3.0",
            "bank_seq_no":"C1074647138",
            "order_time":"2017-10-10 16:02:43",
            "notify_id":"d0387779427a47598357ca5eecbd3ff3",
            "trade_time":"2017-10-10 16:02:43"
        }*/

        if (isset($returnData['order_no']) && $returnData['order_no']) {

            //删除回传参数，避免影响签名
            if(isset($returnData['extra_return_param'])) unset($returnData['extra_return_param']);

            $order_no = $returnData['order_no'];
            $money    = $returnData['order_amount'];

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

                            if ($pay_type == 18) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Wofu::getInstance($conf)->verifySign($returnData);

                                if ($verify) {

                                    $status = 1;

                                    if ($returnData['trade_status'] === 'SUCCESS') {

                                        $status = 0;

                                    }

                                    $pay->vendor_order_no = $returnData['trade_no'];
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
                                                $this->logger->addInfo('Wofu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'SUCCESS';
                                    }
                                }
                            }
                        }
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

        /*回掉
        {   "trade_no":"1004070440",
            "sign_type":"RSA-S",
            "notify_type":"offline_notify",
            "merchant_code":"501502002027",
            "order_no":"201710101602419010243",
            "trade_status":"SUCCESS",
            "sign":"EX7ZgtMUUxyBv3sAXc4+BG9aILU82VSnarf4PZ95rbOqD6+HnUXmqIE8u9dRn1SI7nxLiOh07aMV1dmURXQqGZqMZpFhU3f+vHM/NPsN43XTw58e9iJ4j95GjSPnNqO+6zFnLSD09IQOlz+73OQs6zqPQ1/EH6H4DpwF1KaGpWk=",
            "order_amount":"0.01",
            "interface_version":"V3.0",
            "bank_seq_no":"C1074647138",
            "order_time":"2017-10-10 16:02:43",
            "notify_id":"d0387779427a47598357ca5eecbd3ff3",
            "trade_time":"2017-10-10 16:02:43"
        }*/

        // 日志
        $this->logger->addInfo('Wofu payment notify data:', $returnData);

        if (isset($returnData['order_no']) && $returnData['order_no']) {

            $order_no = $returnData['order_no'];
            $money    = $returnData['order_amount'];

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay) {

                if ($pay->vendor_id && $pay->company_id) {

                    $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                //$bool = Wofu::getInstance($conf)->verifySign($returnData);

                //if ($bool) {

                    if ($returnData['trade_status'] == 'SUCCESS') {

                        return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');
                    } else {

                        $response->getBody()->write('支付失败');
                    }
                //} else {

                //    $response->getBody()->write('请求异常');
                //}
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
