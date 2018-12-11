<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Jinyang;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class JinyangController extends BaseController
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
            "partner":"10033", 商户ID
            "ordernumber":"201711222000019370001", 商户订单号
            "sysnumber":"20171122195954975610", 金阳支付订单号
            "paymoney":"1.00", 订单金额
            "orderstatus":"1", 订单结果
            "sign":"CDBC64E7D46432E47ED57696F3CE1B40", MD5签名
            "attach":"" 备注信息
        }
        */

        //日志
        $this->logger->addInfo('Jinyang payment callback data:', $returnData);


        if (isset($returnData['ordernumber']) && $returnData['ordernumber']) {

            $order_no  = $returnData['ordernumber'];
            $money     = $returnData['paymoney'] ?? 0;
            $mer_order = $returnData['sysnumber'] ?? ''; //商家订单号
            $state    = $returnData['orderstatus'] ?? ''; //支付结果

            $returnMsg = '';

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == '1') {

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

                            if ($pay_type == 23) {
                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Jinyang::getInstance($conf)->verifySign($returnData);
                                if ($verify) {
                                    $status = 0;

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
                                                $this->logger->addInfo('Zesheng payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'OK';
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
        $this->logger->addInfo('Jinyang payment notify data:', $returnData);

        if (isset($returnData['ordernumber']) && $returnData['ordernumber']) {

            $order_no  = $returnData['ordernumber'];
            $money     = $returnData['paymoney'] ?? 0;
            $state    = $returnData['orderstatus'] ?? '';

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state === '1') {

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
