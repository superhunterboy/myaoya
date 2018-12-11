<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Youfu;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class YoufuController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }
        /*回掉
        {
            "memberid":"10038",
            "orderid":"Y201712071528204402820",
            "transaction_id":"20171207152821534953",
            "amount":"0.01",
            "datetime":"20171207152929",
            "returncode":"00",
            "sign":"869CFF16EB6DC52CD361486FF4D82076",
            "attach":""
        }
        */

        //日志
        $this->logger->addInfo('Youfu payment callback data:', $returnData);

        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = ltrim($returnData['orderid'], 'Y');
            $money    = $returnData['amount'] ?? 0; //金额
            $state    = $returnData['returncode'] ?? ''; //支付状态
            $order_me = $returnData['transaction_id'] ?? ''; //支付平台流水号

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == '00') {

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

                            $verify = false;
                            $returnMsg = '';
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 27) {
                                $verify = Youfu::getInstance($conf)->verifySign($returnData);
                                if ($verify) {
                                    $status = 0;

                                    $pay->vendor_order_no = $order_me;
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
                                                $this->logger->addInfo('Youfu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'OK';
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

    //同步通知，没有带参数，支付成功后同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Youfu payment notify data:', $returnData);

        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = ltrim($returnData['orderid'], 'Y');
            $money    = $returnData['amount'] ?? 0; //金额
            $state    = $returnData['returncode'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == '00') {

                if ($pay->vendor_id && $pay->company_id) {

                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

            }
        }

        $response->getBody()->write('支付失败');

    }

    //查询通知
    public function query(Request $request, Response $response, $args)
    {

    }

}