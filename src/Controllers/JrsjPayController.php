<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\JrsjPay;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class JrsjPayController extends BaseController
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

        $ret['payload']           = $_REQUEST["payload"];	//交易流水号
        $ret['state']             = $_REQUEST["state"];	//交易流水号
        $ret['sign']              = $_REQUEST["sign"];	//交易流水号
        $ret['mchNo']             = $_REQUEST["mchNo"];	//交易流水号
        $ret['code']              = $_REQUEST["code"];	//交易流水号

        $vendor_s  = Vendor::where('no', '=', $ret['mchNo'])->first();
        $vendor_sarr=json_decode($vendor_s->key,true);
        $iv = '0102030405060708';

        $decoded = base64_decode($ret['payload']);
        $decrypted = openssl_decrypt($decoded, 'AES-128-CBC', $vendor_sarr['paysecret'], OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
        $resultdecode=$this->pkcs5_unpad_callback ( $decrypted );
        $returnData=json_decode($resultdecode,true);

        /*
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }*/

        $this->logger->addInfo('JrsjPay------- payment callback data:', $_REQUEST);

        if (isset($returnData['tradeNo']) && $returnData['tradeNo']) {
            $order_no = $returnData['tradeNo']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['price']); // 金额
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
                            $verify              = JrsjPay::getInstance($conf)->verifySign($ret);

                            if ($verify) {
                                $status = 1;
                                if ($state == 00) {
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
                                    $data_res['orderno']=$order_no;
                                    $data_res['output']='验证失败:'.$output;
                                    $this->logger->addInfo('JrsjPay------- payment callback data:', $data_res);
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

    public function pkcs5_unpad_callback($text)
    {
        $pad = ord($text {strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
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
