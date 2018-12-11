<?php
namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Renxin;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;
use \Resque;

class RenxinController extends BaseController
{
    // 异步回掉
    public function callback(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        $order_no = $returnData['ordernumber'];
        $money    = sprintf("%.2f", $returnData['paymoney']);

        /**
         *  {
         *       "partner": "20846",
         *       "ordernumber": "201708171913512841404",
         *       "orderstatus": "1",
         *       "paymoney": "1.000",
         *       "sysnumber": "RX1708171914218357514",
         *       "attach": "201708171913512841404",
         *       "sign": "1240052c04cb970f5442956960c38427"
         *   }
         */

        // 日志
        $this->logger->addInfo('Renxin payment callback data:', $returnData);

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = 'error';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 13) {

                                $verify = Renxin::getInstance($conf)->verifySign($returnData);

                            }

                            $time = date('Y-m-d H:i:s');
                            if ($verify) {

                                $status = 1;

                                if ($returnData['orderstatus'] == 1) {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $returnData['sysnumber'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = $time;
                                $pay->remark          = json_encode($returnData);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 13) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => $time,
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 记录日志
                                                $this->logger->addInfo('Renxin payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'ok';

                                    }
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

    // 同步通知
    public function notify(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        $this->logger->addInfo('Renxin payment notify data:', $getDatas);

        $status = 1;

        if ($getDatas['orderstatus'] == 1) {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';

            if (isset($getDatas['ordernumber']) && $getDatas['ordernumber']) {

                $order_no = $getDatas['ordernumber'];

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
