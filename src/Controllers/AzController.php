<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Az;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class AzController extends BaseController
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
        $returnData['p1_MerId']        = $_REQUEST["p1_MerId"];	//交易流水号
        $returnData['r0_Cmd']        = $_REQUEST["r0_Cmd"];	//交易流水号
        $returnData['r1_Code']        = $_REQUEST["r1_Code"];	//交易流水号
        $returnData['r2_TrxId']        = $_REQUEST["r2_TrxId"];	//交易流水号
        $returnData['r3_Amt']        = $_REQUEST["r3_Amt"];	//交易流水号
        $returnData['r4_Cur']        = $_REQUEST["r4_Cur"];	//交易流水号
        $returnData['r5_Pid']        = $_REQUEST["r5_Pid"];	//交易流水号
        $returnData['r6_Order']        = $_REQUEST["r6_Order"];	//交易流水号
        $returnData['r7_Uid']        = "";	//交易流水号
        $returnData['r8_MP']        = "";	//交易流水号
        $returnData['r9_BType']        = $_REQUEST["r9_BType"];	//交易流水号

        $returnData['sign']        = $_REQUEST["hmac"];	//交易流水号

        /*
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }*/

        $this->logger->addInfo('Az payment callback data:', $returnData);

        if (isset($returnData['r6_Order']) && $returnData['r6_Order']) {
            $order_no = $returnData['r6_Order']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['r3_Amt']); // 金额
            $state    = $returnData['r1_Code'] ?? ''; // 支付状态
            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            $isLock=1;
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
                            $verify              = Az::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == '1') {
                                    $status = 0;
                                }
                                $pay->vendor_order_no = $returnData['r2_TrxId'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s');
                                $pay->remark          = json_encode($returnData);
                                $res                  = $pay->save();
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
                                            $this->logger->addInfo('Az payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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

        $this->logger->addInfo('Az payment notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
