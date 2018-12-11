<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Pppay;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class PppayController extends BaseController
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

        //  {
        //     "orderid": "201807231249411602171",
        //     "opstate": "0",
        //     "ovalue": "2",
        //     "systime": "2018/07/23 12:50:42",
        //     "sysorderid": "18072312494365912025",
        //     "completiontime": "2018/07/23 12:50:42",
        //     "attach": "",
        //     "msg": "",
        //     "type": "1001",
        //     "sign": "8fac9a125723ff83b30011a320e68e74",
        //     "RSA_sign": "CPign1aQ7_28JBc4CnzP7uq0_R1CS2LSS8Fq8rgDEgFymV9qinFqlQMXpDRrrg1OyPLL1DU0InVsFBF6TvKHCS2Ika5Uai0iPC7T/r8_OoUwBGF9svj1zw4aBLMbt1bd8O3c7VYYwFkSCxd3TiRywDI6fIuRUwcPqEUKBiSGdBw="
        // }

        //日志
        $this->logger->addInfo('Pppay payment callback data:', $returnData);

        if (isset($returnData['orderId']) && $returnData['orderId']) {

            $order_no = $returnData['orderId'];
            $money    = sprintf("%.2f", intval($returnData['goodsAmount']));
            $opstate  = $returnData['code'] ?? '';
            unset($returnData['code']);
            $array = array('1' => '111');
            $array1 = array('money' => $money,'order_no'=>$order_no);
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
                            $returnMsg           = 'fail';
                            $verify              = Pppay::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($opstate == 0) {
                                    $status = 0;
                                }
                                $pay->vendor_order_no = $returnData['preOrderId'];
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
                                            $this->logger->addInfo('Pppay payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
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

        $this->logger->addInfo('Pppay payment notify data:', $returnData);

        $response->getBody()->write('success');

        return $response;
    }
}
