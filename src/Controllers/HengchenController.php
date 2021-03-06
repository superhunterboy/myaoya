<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Hengchen;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class HengchenController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }
        //编码转换
        $returnData['msg'] = iconv('gb2312', 'utf-8', $returnData['msg']);
        /*回掉
        {
            "orderid":"", 商户订单号
            "opstate":"", 订单结果
            "ovalue":"", 订单金额
            "sign":"", MD5签名
            "sysorderid":"", 订单号
            "systime":"", 恒辰支付平台订单时间
            "attach":"", 备注信息
            "msg":"" 订单结果说明
        }{
            "orderid":"201712191149547314954",
            "opstate":"0",
            "ovalue":"1.00",
            "systime":"2017/12/19 11:49:07",
            "attach":"",
            "msg":"支付成功",
            "sign":"3e2c8dbfa206babba83aa98bcd0ba899"
        }
        */

        //日志
        $this->logger->addInfo('Hengchen payment callback data:', $returnData);

        $returnMsg = '数据错误';
        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = $returnData['orderid'];
            $money    = $returnData['ovalue'] ?? 0;     //金额
            $state    = $returnData['opstate'] ?? '';    //支付状态
            $orderMer = $returnData['sysorderid'] ?? date('YmdHis');    //支付平台流水号;没有时用支付时间代替

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == '0') {

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

                            if ($pay_type == 29) {
                                $verify = Hengchen::getInstance($conf)->verifySign($returnData);
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
                                                $this->logger->addInfo('Hengchen payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'opstate=0';
                                    }
                                }
                            }
                        }
                    }
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
        $this->logger->addInfo('Hengchen payment notify data:', $returnData);

        if (isset($returnData['orderid']) && $returnData['orderid']) {

            $order_no = $returnData['orderid'];
            $money    = $returnData['ovalue'] ?? 0;     //金额
            $state    = $returnData['opstate'] ?? '';    //支付状态

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