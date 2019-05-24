<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Ly;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class LyController extends BaseController
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
        $this->logger->addInfo('Ly payment callback data:', $returnData);
        $pay = Pay::where('order_no', '=', $returnData['orderNo'])->first();

        if(empty($pay->notify_url)){    //##正常
          if (isset($returnData['orderNo']) && $returnData['orderNo']) {
            $order_no = $returnData['orderNo']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['orderPrice']); // 金额
            $state    = $returnData['state'] ?? ''; // 支付状态

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            if ($isLock) {
                //$pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();
                $pay = Pay::where('order_no', '=', $order_no)->first();
                $money = $pay->money;
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
                            $verify              = Ly::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($state == 1) {
                                    $status = 0;
                                }
                                $pay->vendor_order_no = $returnData['trxNo'];
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
                                            $this->logger->addInfo('Ly payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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
        }else{
          if (isset($returnData['orderNo']) && $returnData['orderNo']) {
              $order_no = $returnData['orderNo']; // 上传订单号
              $money    = sprintf("%.2f", $returnData['orderPrice']); // 金额
              $state    = $returnData['state'] ?? ''; // 支付状态
              // 加锁防止并发回调
              $redisLockey = 'redisLock:' . $order_no;
              $isLock      = $this->redisLock->lock($redisLockey, 120);
              if ($isLock) {
                  $pay = Pay::where('order_no', '=', $order_no)->first();
                  $money = $pay->money;
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
                              $verify              = Ly::getInstance($conf)->verifySign($returnData);
                              if ($verify) {
                                  $status = 0;
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
                                  $pay->vendor_order_no = $returnData['out_trade_no'];
                                  $pay->status          = $status;
                                  $pay->pay_datetime    = date('Y-m-d H:i:s');
                                  $pay->notify_msg      = $output;
                                  $pay->remark          = json_encode($returnData);
                                  $res                  = $pay->save();
                                  $returnMsg="success";
                                  $response->getBody()->write($returnMsg);

                              }
                              $response->getBody()->write($returnMsg);
                          }
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

        $this->logger->addInfo('Af payment notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
