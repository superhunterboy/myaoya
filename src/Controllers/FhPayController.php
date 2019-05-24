<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\FhPay;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class FhPayController extends BaseController
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

        $returnData['input_charset']           = $_REQUEST["input_charset"];	//交易流水号
        $returnData['sign_type']              = $_REQUEST["sign_type"];	//交易流水号
        $returnData['sign']            = $_REQUEST["sign"];	//交易流水号
        $returnData['request_time']             = $_REQUEST["request_time"];	//交易流水号
        $returnData['trade_id']            = $_REQUEST["trade_id"];	//交易流水号
        $returnData['out_trade_no']                 = $_REQUEST["out_trade_no"];	//交易流水号
        $returnData['amount_str']             = $_REQUEST["amount_str"];	//交易流水号
        $returnData['amount_fee']         = $_REQUEST["amount_fee"];	//交易流水号
        $returnData['status']           = $_REQUEST["status"];	//交易流水号
        $returnData['for_trade_id']           = $_REQUEST["for_trade_id"];	//交易流水号
        $returnData['business_type']               = $_REQUEST["business_type"];	//交易流水号
        $returnData['remark']        = $_REQUEST["remark"];	//交易流水号
        $returnData['create_time']               = $_REQUEST["create_time"];	//交易流水号
        $returnData['modified_time']          = $_REQUEST["modified_time"];	//交易流水号


        /*
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }*/

        $this->logger->addInfo('HFpay------- payment callback data:', $_REQUEST);

        if (isset($returnData['out_trade_no']) && $returnData['out_trade_no']) {
            $order_no = $returnData['out_trade_no']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['amount_str']); // 金额
            $state    = $returnData['status'] ?? ''; // 支付状态
            // 加锁防止并发回调
            //$redisLockey = 'redisLock:' . $order_no;
            //$isLock      = $this->redisLock->lock($redisLockey, 120);
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
                            $verify              = FhPay::getInstance($conf)->verifySign($returnData);

                            if ($verify) {
                                $status = 1;
                                if ($state == 1) {
                                    $status = 0;
                                }

                                //#START#通知bbin上分请求######=============================
                                $notifyUrl=$pay->notify_url;   //回调通知bbin地址
                                $params = [
                                    'number' =>   $conf['parterNo'],
                                    'order_id' => $order_no,
                                    'amount' => $money,
                                    'ref_id' => "",
                                ];

                                $encodeArr = [];

                                foreach ($params as $k => $v) {
                                    $encodeArr[$k] = $v;
                                }

                                ksort($encodeArr);
                                $encodeArr['key'] = $conf['parterKey'];
                                $params['sign'] = strtoupper(md5(urldecode(http_build_query($encodeArr))));

                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $notifyUrl);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                $output = curl_exec($ch);
                                if ($output != 'success') {
                                    echo '验证失败:' . $output;
                                    exit;
                                }
                                //#END#通知bbin上分请求######=============================
                                if($output == "success"){
                                    $pay->rk_user      = "system";
                                    $pay->recharge_msg      = "充值成功";
                                    $pay->rk_status      = 1;
                                }
                                $pay->vendor_order_no = $returnData['out_trade_no'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s');
                                $pay->notify_msg      = $output;
                                $pay->remark          = json_encode($returnData);
                                $res                  = $pay->save();
                                $returnMsg="success";
                                $response->getBody()->write($returnMsg);
                            }
                            //$response->getBody()->write($returnMsg);
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

        $this->logger->addInfo('Baisheng payment notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
