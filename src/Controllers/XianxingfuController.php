<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Xianxingfu;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class XianxingfuController extends BaseController
{
	/**
     * 先行付支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function callback(Request $request, Response $response, $args)
    {

        $returnData = [];
        $getDatas = $request->getUri()->getQuery();
        parse_str(iconv('gbk', 'utf-8', urldecode($getDatas)), $returnData);
        if ($request->isPost()) {
            $postDatas = file_get_contents('php://input');
            parse_str(iconv('gbk', 'utf-8', $postDatas), $returnData);
        }

        /*{
        "orderid":"201709271436581433711",
        "opstate":"0",
        "ovalue":"1.00",
        "systime":"2017/09/27 14:37:32",
        "sysorderid":"1709271436551190206",
        "completiontime":"2017/09/27 14:37:32",
        "attach":"",
        "msg":"支付成功",
        "sign":"22b6dca10fd87e2d498116e9e3489301"
        }*/

        $this->logger->addInfo('Xianxingfu payment callback data:', $returnData);

        if (isset($returnData['sysorderid']) && $returnData['sysorderid']) {

            $order_no = $returnData['orderid'];
            $money    = $returnData['ovalue'];

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = '';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 17) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Xianxingfu::getInstance($conf)->verifySign($returnData);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($returnData['opstate'] == '0') {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $returnData['sysorderid'];
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
                                            // 日志
                                            $this->logger->addInfo('Xianxingfu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                        }
                                    }

                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);

                                    // $returnMsg = $company->url . '/success/index.html';
                                    $returnMsg = 'opstate=0';

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
     * 先行付支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function notify(Request $request, Response $response, $args)
    {
        $returnData = [];
        $getDatas = $request->getUri()->getQuery();
        parse_str(iconv('gbk', 'utf-8', urldecode($getDatas)), $returnData);
        if ($request->isPost()) {
            $postDatas = file_get_contents('php://input');
            parse_str(iconv('gbk', 'utf-8', $postDatas), $returnData);
        }

        /*{
        "orderid":"201709271436581433711",
        "opstate":"0",
        "ovalue":"1.00",
        "systime":"2017/09/27 14:37:32",
        "sysorderid":"1709271436551190206",
        "completiontime":"2017/09/27 14:37:32",
        "attach":"",
        "msg":"支付成功",
        "sign":"22b6dca10fd87e2d498116e9e3489301"
        }*/

        $this->logger->addInfo('Xianxingfu payment notify data:', $returnData);

        $status = 1;

        if (isset($returnData['sysorderid']) && $returnData['sysorderid'] && $returnData['opstate'] == '0') {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';

            if (isset($returnData['orderid']) && $returnData['orderid']) {

                $order_no = $returnData['orderid'];

            }

            if ($order_no) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->company_id) {

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($company) {

                            if ($company->url) {

                                // header("Location: " . $company->url . '/success/index.html');
                                // exit();
                                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

                            } else {

                                $response->getBody()->write('Url is blank, Please contact webmaster.');
                            }
                        }
                    }
                }
            }

        } else {

            $response->getBody()->write('failure');
        }

        return $response;
    }
}