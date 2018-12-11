<?php

namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Resque;
use Weiming\Controllers\BaseController;
use Weiming\Jobs\AutoRechargeJob;
use Weiming\Libs\Payments\Zhongxin;
use Weiming\Libs\Utils;
use Weiming\Models\Company;
use Weiming\Models\Pay;
use Weiming\Models\Vendor;

class ZhongxinController extends BaseController
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
        "status":"1",订单状态
        "customerid":"10975",商户编号
        "sdpayno":"2017112417162910497",平台订单号
        "sdorderno":"201711241716269951629",商户订单号
        "total_fee":"1.00",交易金额
        "paytype":"weixin",支付类型
        "remark":"",订单备注说明
        "sign":"f53b09c4f641ca7a7ecb17428486bf78"md5验证签名串
        }
         */

        //日志
        $this->logger->addInfo('Zhongxin payment callback data:', $returnData);

        if (isset($returnData['sdorderno']) && $returnData['sdorderno']) {

            $order_no  = $returnData['sdorderno'];
            $money     = $returnData['total_fee'] ?? 0;
            $mer_order = $returnData['sdpayno'] ?? ''; //商家订单号
            $retCode   = $returnData['status'] ?? ''; //支付状态

            $returnMsg = '';

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
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify   = false;
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 46) {
                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Zhongxin::getInstance($conf)->verifySign($returnData);
                                if ($verify) {
                                    $status = 1;

                                    if ($retCode == '1') {
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
                                                $this->logger->addInfo('Zhongxin payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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

            $response->getBody()->write($returnMsg);
        }
        return $response;
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Zhongxin payment notify data:', $returnData);

        if (isset($returnData['sdorderno']) && $returnData['sdorderno']) {

            $order_no = $returnData['sdorderno'];
            $money    = $returnData['total_fee'] ?? 0;
            $retCode  = $returnData['status'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $retCode === '1') {

                if ($pay->vendor_id && $pay->company_id) {

                    $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

            }
        }

        $response->getBody()->write('支付失败');

    }
}
