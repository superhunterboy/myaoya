<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Hengxing;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class HengxingController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {
        $input = file_get_contents("php://input", 'r');
        $returnJson = base64_decode($input, true);
        $returnData = json_decode($returnJson, true);
        /*回掉
        {
            "charset":"UTF-8",
            "version":"1.0",
            "merchantId":"10000088",
            "orderId":"201803171309158000915",
            "payOrderId":"102018031710074237",
            "payType":"DQP",
            "tranAmt":"10.00",
            "orderSts":"PD",
            "tranTime":"20180317131011",
            "remark":"",
            "orderDesc":"",
            "extRemark":"",
            "signType":"MD5",
            "signData":"22F1AC9E76170F1FE7E542A9CEAD191D"
        }
        */

        //日志
        $this->logger->addInfo('Hengxing payment callback data:', $returnData);

        if (isset($returnData['orderId']) && $returnData['orderId']) {

            $order_no  = $returnData['orderId'];
            $money     = $returnData['tranAmt'] ?? 0;
            $mer_order = $returnData['payOrderId'] ?? date('YmdHis'); //商家订单号
            $retCode   = $returnData['orderSts'] ?? 'error'; //支付状态

            $returnMsg = '';

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 45) {
                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Hengxing::getInstance($conf)->verifySign($returnData);
                                if ($verify) {

                                    $status = 1;
                                    if ($retCode == 'PD') {
                                        $status = 0;
                                    }

                                    $pay->vendor_order_no = $mer_order;
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
                                                $this->logger->addInfo('Hengxing payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'result=SUCCESS';
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

    //同步通知(没有)
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Hengxing payment notify data:', $returnData);

        if (isset($returnData['orderId']) && $returnData['orderId']) {

            $order_no  = $returnData['orderId'];
            $money     = $returnData['tranAmt'] ?? 0;
            $retCode   = $returnData['orderSts'] ?? 'error'; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $retCode == 'PD') {

                if ($pay->vendor_id && $pay->company_id) {

                    $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

            }
        } elseif (empty($returnData)) {
            return $response->getBody()->write('正在确认充值，稍后就可入账');
        }

        $response->getBody()->write('支付失败');

    }
}
