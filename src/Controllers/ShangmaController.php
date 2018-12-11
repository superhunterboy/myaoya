<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Shangma;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class ShangmaController extends BaseController
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
            "amount":"2.0",
            "outTradeNo":"201712131816213991621",
            "remark":"smart",
            "resultCode":"00",
            "resultMsg":"操作成功",
            "returnCode":"2",
            "sign":"FBd39fXO8cEsToDgHdaO+Od+IEQht6MhOdXaOVxTU2wxAF9XntPgBPj6dDC0r5ulUx8a3AI56pJOzhV/kFgJBIye1MuDl5+DumgGljTnaSc2A0a8xU6m8kRqADk/liD1MjI4sJzr1mFUCOXyj6q7U5WIdd1sZdXPW8D5tCHzYiF1LiVx73DXJKBWdNmP/vAZGbsB5H7l4dTBnuNtGyEYnyneD1LAvB9SpDv35zKe7dpMSgNpyg2dRsM0IH04VE4y0HrSTI2x0bVbCWShIqUW+USXh2igHNDA6mEAb/aEGj5zqexDVO6WPiuVx76BEqS9fQFvjDCUCSRJLmK4WcWg+A=="
        }
        */

        //日志
        $this->logger->addInfo('Shangma payment callback data:', $returnData);

        if (isset($returnData['outTradeNo']) && $returnData['outTradeNo']) {

            $order_no = $returnData['outTradeNo'];
            $money    = $returnData['amount']/100 ?? 0;     //金额
            $money    = sprintf("%.2f",$money);             //格式化金额
            $state    = $returnData['returnCode'] ?? '';    //支付状态
            $order_me = $returnData['outTradeNo'] ?? '';    //支付平台流水号

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == '2') {

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

                            if ($pay_type == 28) {
                                $verify = Shangma::getInstance($conf)->verifySign($returnData);
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
                                                $this->logger->addInfo('Shangma payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'success';
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
        $this->logger->addInfo('Shangma payment notify data:', $returnData);

        if (isset($returnData['outTradeNo']) && $returnData['outTradeNo']) {

            $order_no = $returnData['outTradeNo'];
            $money    = $returnData['amount']/100 ?? 0;     //金额
            $money    = sprintf("%.2f",$money);             //格式化金额
            $state    = $returnData['returnCode'] ?? '';    //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == '2') {

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