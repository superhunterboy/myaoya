<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Tengkun;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class TengkunController extends BaseController
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
                "orderid":"201801051439386143938", 商户订单号
                "opstate":"0", 交易状态
                "ovalue":"1.00", 订单金额
                "sysorderid":"j1801051439209827", 平台订单号
                "systime":"2018/01/05 14:39:51",
                "attach":"", 扩展返回
                "msg":"",
                "sign":"f99275a705aab8b266f0014dd727e564" 签名
            }
        */

        //日志
        $this->logger->addInfo('Tengkun payment callback data:', $returnData);

        $returnMsg = '';
        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = $returnData['orderid']; //上传订单号
            $money    = $returnData['ovalue'] ?? 0; //金额
            $state    = $returnData['opstate'] ?? ''; //支付状态
            $orderMer = $returnData['sysorderid'] ?? date('YmdHis'); //流水号

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($state == '0') {//$isLock &&

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
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 34) {
                                $verify = Tengkun::getInstance($conf)->verifySign($returnData);
                                if ($verify) {
                                    $status = 0;

                                    $pay->vendor_order_no = $orderMer;
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
                                                $this->logger->addInfo('Tengkun payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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

        }
        return $response->getBody()->write($returnMsg);
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Tengkun payment notify data:', $returnData);

        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = $returnData['orderid']; //上传订单号
            $money    = $returnData['ovalue'] ?? 0; //金额
            $state    = $returnData['opstate'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == '0') {

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