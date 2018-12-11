<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\YafuNew;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class YafuNewController extends BaseController
{
	/**
     * 新雅付支付回调
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

        /*{"consumerNo":"20781",
	        "merOrderNo":"201709221606047980607",
	        "merRemark":"201709221606047980607",
	        "orderNo":"20170922160607783267",
	        "orderStatus":"1",
	        "payType":"0201",
	        "sign":"D83B6E4557C5C0861FC8AFBAEC02C6AE",
	        "transAmt":"0.01",
	        "version":"3.0"
	    }*/

        $this->logger->addInfo('YafuNew payment callback data:', $returnData);

        if (isset($returnData['merOrderNo']) && $returnData['merOrderNo']) {

            $order_no = $returnData['merOrderNo'];
            $money    = $returnData['transAmt'];

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

                            if ($pay_type == 16) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = YafuNew::getInstance($conf)->verifySign($returnData);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($returnData['orderStatus'] == 1) {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $returnData['orderNo'];
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
                                            $this->logger->addInfo('YafuNew payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                        }
                                    }

                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);

                                    // $returnMsg = $company->url . '/success/index.html';
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
     * 新雅付支付通知
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

        // {
        //     "merOrderNo": "201705241335086743510",
        //     "transAmt": "0.1",
        //     "orderStatus": "1",
        //     "orderNo": "20170524133510618905",
        //     "code": "",
        //     "sign": "DD596A752593771C74400B348B315A7C",
        //     "consumerNo": "20133"
        // }

        $this->logger->addInfo('YafuNew payment notify data:', $returnData);

        $status = 1;

        if (isset($returnData['orderStatus']) && $returnData['orderStatus'] && $returnData['orderStatus'] == 1) {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';

            if (isset($returnData['merOrderNo']) && $returnData['merOrderNo']) {

                $order_no = $returnData['merOrderNo'];

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