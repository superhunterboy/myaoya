<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Hengtong;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class HengtongController extends BaseController
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
        $this->logger->addInfo('Hengtong payment callback data:', $returnData);

        // {
        //     "orderid": "201806061757213825806",
        //     "result": "1",
        //     "amount": "97.91",
        //     "systemorderid": "Q180606175721457551980289",
        //     "completetime": "20180606175807",
        //     "notifytime": "20180606180007",
        //     "sign": "dc473fac12ac3c623b31133cf2f89ce6",
        //     "attach": "CZ",
        //     "sourceamount": "100.00",
        //     "payamount": "99.40"
        // }

        if (isset($returnData['orderid']) && $returnData['orderid']) {
            $order_no = $returnData['orderid']; // 上传订单号
            $money    = $returnData['sourceamount'] ?? 0; // 金额
            $state    = $returnData['result'] ?? 2; // 支付状态

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();
                if ($pay) {
                    if ($pay->vendor_id && $pay->company_id) {
                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();
                        if ($vendor && $company) {
                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;
                            $verify              = false;
                            $returnMsg           = 'failure';
                            $verify              = Hengtong::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 1) {
                                    $status = 0;
                                }
                                $pay->vendor_order_no = $returnData['systemorderid'] ?? '';
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s', strtotime($returnData['completetime']));
                                $pay->remark          = json_encode($returnData);
                                $res                  = $pay->save();
                                if ($res) {
                                    // 自动入款
                                    if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                        $requestParams = [
                                            'account'      => $pay->user,
                                            'fee'          => $returnData['payamount'] ?? 0,
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
                                            $this->logger->addInfo('Hengtong payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                        }
                                    }
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

        $this->logger->addInfo('Hengtong payment notify data:', $returnData);

        // {
        //     "orderid": "201806061757213825806",
        //     "result": "1",
        //     "amount": "97.91",
        //     "systemorderid": "Q180606175721457551980289",
        //     "completetime": "20180606175807",
        //     "notifytime": "20180606180007",
        //     "sign": "dc473fac12ac3c623b31133cf2f89ce6",
        //     "attach": "CZ",
        //     "sourceamount": "100.00",
        //     "payamount": "99.40"
        // }

        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = $returnData['orderid']; //上传订单号
            $money    = $returnData['sourceamount'] ?? 0; //金额
            $state    = $returnData['result'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == 1) {

                if ($pay->company_id) {

                    $company = Company::where('id', '=', $pay->company_id)->first();

                    if ($company) {

                        return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');
                    
                    }
                }
            }
        }

        $response->getBody()->write('支付失败');

        return $response;
    }
}
