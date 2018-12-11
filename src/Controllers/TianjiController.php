<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Tianji;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class TianjiController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        //$returnData = $request->getQueryParams();
        //if ($request->isPost()) {
        //    $returnData = $request->getParsedBody();
        //}
        //if (empty($returnData) || !is_array($returnData)) {
            $returnJson = file_get_contents('php://input');
            $returnData = json_decode($returnJson, true);
            if (empty($returnData)) {
                $json       = substr($returnJson, strpos($returnJson, '"') + 1, strrpos($returnJson, ':') - 3);
                $returnData = json_decode($json, true);
            }
        //}
        /*回掉
        {
        "merchant_id": "100018",
        "order_id": "201801061316007661600",
        "trans_amt": "200",
        "arrival_amt": 197,
        "back_url": "http: //157a_com/payment/tianji/callback",
        "send_time": "20180106131600",
        "resp_desc": "交易成功",
        "resp_code": "0000",
        "sign": "0326bbb89b9324f3d423438f9a124dd9"
        }
         */

        //日志
        $this->logger->addInfo('Tianji payment callback data:', $returnData);

        $returnMsg = '';
        if (isset($returnData['order_id']) && $returnData['order_id']) {

            $order_no = $returnData['order_id']; //上传订单号
            $money    = $returnData['trans_amt'] ?? 0; //金额
            $money    = sprintf("%.2f", $money / 100); //格式化
            $state    = $returnData['resp_code'] ?? ''; //支付状态
            $orderMer = $returnData['send_time'] ?? date('YmdHis'); //流水号

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == '0000') {

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

                            $verify   = false;
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 35) {
                                $verify = Tianji::getInstance($conf)->verifySign($returnData);
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
                                                $this->logger->addInfo('Tianji payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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
        if (empty($returnData) || !is_array($returnData)) {
            $returnJson = file_get_contents('php://input');
            $returnData = json_decode($returnJson, true);
        }

        // 日志
        $this->logger->addInfo('Tianji payment notify data:', $returnData);

        if (isset($returnData['order_id']) && $returnData['order_id']) {

            $order_no = $returnData['order_id']; //上传订单号
            $money    = $returnData['trans_amt'] ?? 0; //金额
            $state    = $returnData['resp_code'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == '0000') {

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