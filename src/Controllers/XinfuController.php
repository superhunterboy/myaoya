<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Xinfu;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class XinfuController extends BaseController
{

    //异步回调
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }
        //$returnData['resp_desc'] = urldecode($returnData['resp_desc']); //url解码
        /*回掉
            应答返回码   resp_code   000  成功
            应答返回描述  resp_desc 返回码的对应中文描述，中文为UTF-8字符集URLEncode编码后的字符串，商户应解码获取原文
            商户客户号   cust_id
            订单号 ord_id
            平台交易唯一标识号   platform_seq_id 8位本平台日期+10位系统流水号
            交易金额    trans_amt
            商户私有域   mer_priv
            扩展域 extension
            通知校验ID  notify_id   变长120位 用作验证接口中上送，校验异步通知合法性不参与签名
            签名  check_value 定长32位   必须
            {"
                check_value":"29647F886D4DA8C40802FC972F08E31C",
                "cust_id":"4000116800",
                "extension":"",
                "mer_priv":"",
                "notify_id":"800349B01FD7C46E93163A7E972A1DA9764BA89EF6F4BA8B15A99D1FB81E720D",
                "ord_id":"12250939054323905",
                "platform_seq_id":"201712250019403805",
                "resp_code":"000",
                "resp_desc":"交易成功",
                "trans_amt":"1.00"
            }
        */

        //日志
        $this->logger->addInfo('Xinfu payment callback data:', $returnData);

        $returnMsg = '';
        if (isset($returnData['ord_id']) && $returnData['ord_id']) {

            $order_no = date('Y') . $returnData['ord_id']; //上传订单号
            $money    = $returnData['trans_amt'] ?? 0; //金额
            $state    = $returnData['resp_code'] ?? ''; //支付状态
            $orderMer = $returnData['platform_seq_id'] ?? date('YmdHis'); //流水号

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock && $state == '000') {

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

                            $verify = false;
                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 33) {
                                $verify = Xinfu::getInstance($conf)->verifySign($returnData);
                                if ($verify) {
                                    $status = 0;

                                    $pay->vendor_order_no = $orderMer;
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
                                                $this->logger->addInfo('Xinfu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'ECHO_SEQ_ID=' . $returnData['ord_id'];
                                    }
                                }
                            }
                        }
                    }
                } else {

                    $returnMsg = '订单号错误';
                }

            }

        }
        return $response->getBody()->write($returnMsg);
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {

        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // 日志
        $this->logger->addInfo('Xinfu payment notify data:', $returnData);
        //通知
        /*{"
            resp_code":"000",
            "resp_desc":"%E4%BA%A4%E6%98%93%E6%88%90%E5%8A%9F",
            "cust_id":"4000116800",
            "ord_id":"12250939054323905",
            "platform_seq_id":"201712250019403805",
            "trans_amt":"1.00",
            "mer_priv":"",
            "extension":"",
            "check_value":"29647F886D4DA8C40802FC972F08E31C"
        }*/

        if (isset($returnData['ord_id']) && $returnData['ord_id']) {

            $order_no = date('Y') . $returnData['ord_id']; //上传订单号
            $money    = $returnData['trans_amt'] ?? 0; //金额
            $state    = $returnData['resp_code'] ?? ''; //支付状态

            $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

            if ($pay && $state == '000') {

                if ($pay->vendor_id && $pay->company_id) {

                    $company = Company::where('id', '=', $pay->company_id)->first();
                }

                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

            }
        }

        $response->getBody()->write('支付失败');

    }

    //查询通知
    public function query(Request $request, Response $response, $args)
    {

    }

}