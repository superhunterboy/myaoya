<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\ZiyoufuNew;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class ZiyoufuNewController extends BaseController
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
            "OrderNo":"ZF20171124261001134943",平台订单号
            "MerchantNo":"8000000000011",商户号
            "Amount":1000,交易金额（如1元=100）
            "OutTradeNo":"201711241026014972601",外部订单号
            "RetCode":"00",交易结果码，“00”交易成功，其他失败
            "RetMsg":"支付成功",交易结果
            "TradeTime":"2017-11-24 10:26:22",交易时间
            "Attach":"201711241026014972601",交易时传入的附加信息
            "Sign":"9deef18fc7aab584b21208441aac51ef"签名(MD5加密)
        }
        */

        //日志
        $this->logger->addInfo('ZiyoufuNew payment callback data:', $returnData);


        if (isset($returnData['OutTradeNo']) && $returnData['OutTradeNo']) {

            $order_no  = $returnData['OutTradeNo'];
            $money     = $returnData['Amount']/100 ?? 0;
            $mer_order = $returnData['OrderNo'] ?? ''; //商家订单号
            $retCode   = $returnData['RetCode'] ?? ''; //支付状态

            $returnMsg = '';

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $retCode == '00') {//$isLock && $retCode == '00'

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

                            if ($pay_type == 21) {
                                // payType 3 支付宝，2 微信，1 网银
                                $verify = ZiyoufuNew::getInstance($conf)->verifySign($returnData);
                                if ($verify) { //echo 'verify';
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
        $this->logger->addInfo('ZiyoufuNew payment notify data:', $returnData);

        if (isset($returnData['OutTradeNo']) && $returnData['OutTradeNo']) {

            $order_no  = $returnData['OutTradeNo'];
            $money     = $returnData['Amount']/100 ?? 0;
            $state = $returnData['RetCode'] ?? '';

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state === '00') {

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