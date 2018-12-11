<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Ylsl;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class YlslController extends BaseController
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
        echo "+++";
        if ($request->isPost()) {
            $returnData = $request->getQueryParams();
        }


        echo "<pre>";
        print_r($returnData);
        echo "</pre>";

        $sign=$returnData['sign'];
        unset($returnData['sign']);

        ksort($returnData);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($returnData as $key => $val) {
            $msg .= $key.$val;
        }
        echo "----";
        //$msg = $msg . "e5b303780000a071005322365bd6a71detqqk62fs";
        echo md5(strtolower($msg))."--------".$sign;
        die();






        die();

        //$this->logger->addInfo('Ylsl payment callback data:', $returnData);

        if (isset($returnData['orderid']) && $returnData['orderid']) {
            $order_no = $returnData['orderid']; // 上传订单号
            $money    = sprintf("%.2f", $returnData['money'] / 100); // 金额
            $state    = $returnData['orderState '] ?? ''; // 支付状态

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

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
                            $verify              = Ylsl::getInstance($conf)->verifySign($returnData);

                            if ($verify) {
                                $status = 1;
                                if ($state == 1) {
                                    $status = 0;
                                }
                                $pay->vendor_order_no = $returnData['serialId'];
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
                                            $this->logger->addInfo('Ylsl payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                        }
                                    }
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = 'SUCCESS';
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

        $this->logger->addInfo('Xunjie payment notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
