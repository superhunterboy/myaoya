<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;

class VPayController extends BaseController
{
    /**
     * 短信通知的异步回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function notify(Request $request, Response $response, $args)
    {
        $returnData = $request->getParsedBody();

        /**
            Array
            (
                [bankCode] => ICBC
                [cardNo] => 9736
                [money] => 150.21
                [cashInTime] => 2019-01-17 19:01:00
            )
         */
        //日志
        $this->logger->addInfo('VPay payment callback data:', $returnData);
        $returnMsg = '';
        if (isset($returnData['bankCode']) && isset($returnData['cardNo']) && isset($returnData['money']))
        {
            $money = $returnData['money'];
            $depositMoney = $money * 100;     //金额（单位：分）
            $order_no=date('Ymd').'-'.$returnData['bankCode'].'-'.$returnData['cardNo'].'-'.$depositMoney;  //订单号格式 20190126-ABC-9736-99.58
            $pay = Pay::where('order_no', '=', $order_no)->first();
            if ($pay)
            {
                if ($pay->company_id) {
                    $company = Company::where('id', '=', $pay->company_id)->first();
                    if ($company) {
                        $pay->status          = 0;  //成功
                        $pay->pay_datetime    = $returnData['cashInTime'];
                        $pay->remark          = json_encode($returnData);
                        $res                  = $pay->save();

                        //
                        if(!empty($pay->notify_url)){   //##BBIN自接
                            if ($res) {
                                $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();

                                $conf['parterNo']    = $vendor->no;
                                $conf['parterKey']   = $vendor->key;
                                $conf['callbackUrl'] = $vendor->callback_url;
                                $conf['notifyUrl']   = $vendor->notify_url;

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
                                    $data_res['orderno']=$order_no;
                                    $data_res['output']='验证失败:'.$output;
                                    exit;
                                }
                                //#END#通知bbin上分请求######=============================
                                if($output == "success"){
                                    $pay->rk_user      = "system";
                                    $pay->recharge_msg      = "充值成功";
                                    $pay->rk_status      = 1;
                                }
                            }
                        }else{  //##正常
                            if ($res) {
                                // 自动入款
                                if ($pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                    $requestParams = [
                                        'account'      => $pay->user,
                                        'fee'          => $money,
                                        'orderNo'      => $order_no,
                                        'rechargeTime' => date('Y-m-d H:i:s'),
                                    ];
                                    $requestParams['sign'] = Utils::generateSignature($requestParams);
                                    $token             = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                    if ($token) {
                                        // 1 已加入队列
                                        $pay->recharge_status = 1;
                                        $pay->recharge_msg    = '正在自动入款';
                                        $pay->queue_job_id    = $token;
                                        $pay->save();
                                        // 记录日志
                                        $this->logger->addInfo('VPay payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                    }
                                }
                                $returnMsg = 'success';
                            }
                            $response->getBody()->write($returnMsg);
                        }

                    }
                }
            }
            else
            {
                $response->getBody()->write('INVALID ORDER NUMBER='.$order_no);
            }
        }
        else
        {
            $response->getBody()->write('INVALID POST DATA='.json_encode($returnData));
        }
        return $response;
    }


}
