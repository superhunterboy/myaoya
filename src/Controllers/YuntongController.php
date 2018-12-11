<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Yuntong;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class YuntongController extends BaseController
{
    //异步回调
    public function callback(Request $request, Response $response, $args)
    {
        $postDatas = [];
        $getDatas  = $request->getQueryParams();
        if ($request->isPost()) {
            $postDatas = $request->getParsedBody();
        }

        $returnData = empty($getDatas) ? $postDatas : $getDatas;

        /*
        {
        "encryptData": "AB77AA822FE1AD6816FBC4CA6A7A4EEB8B7A41D73E0463A4F5FB0998B0CA4F17120A428F06B77563E04E61E211CDC2A486725DE1BBC7E7A24D3427C2AF2053B1E19D25779437D02448647CB29CEC19803321AA2159AB496AB14627FB8C8092EF7E4FB336E1A18D1FB7C3E164D9BDB99631D757178028250C445FC8434C1AD4CA4FF106E37138E06FCDE435C094ED1A1267135E368AFD6C584E9C88D67D44AC57754E2C318AB3ECCB962864229A8599B90D30CC3D56FBE9D77A2F6DA9AED1D351DB7872ABBA83ECBA422079C69769577E51F3E0CD2D6BA8459A503E931B70EFA815EF39C0DFB7A89ED8C2F5F2EFB95CA06001119BC7590D9A210626B8537CA12CEA328279EFEFD13D5878394F03D603002836B0B5AFA321F4A53C9483EF69457BDB8DC0D72B5A36954E84B14E3BE88C0CA83D3A35FAC96863B3E7A5A339C9A75A0637396E8BE8E0B5C1FFEBFFF2FD92884A087711220D055CF89386870F950ADB1ED69F71F6E4EEE9FF1F5CCEBBF30E4A15A9203D9E0F3C9B93670C6677E022471DE879FBAEB3354CEF6374E1169F1310B1C07622932A81B24613B99011F2CE712F04965B8E6DCF54E463E91D4B77F171",
        "sign": "QaMe8gF1Zd1Y+wOHqZjFST9pHwqwNyxvTPxjEBDitC0qZv6ZH3jQE9qktQ+7MbtCow8x0B3xJYdUgXY1PUyNs4QcYygUhbprEkyGzG34W9xZpmh/6iGqGLqFKLYIbjjuEWKUl3c1DsxTv8NgLFjUbaNxo0Q1JjhFeKm06LKzLzdaqKJxgxvTYJ05CXRGfAhmLuTjFd0vVI4CfFnz1Ondamy6NfdIXurOlz09wC3uDtT/ZzbYZ0A2N5+6HnbXVnHhth8mJYfo1d0o6XEULqTAaHs8iqn8KQ2nngBiC9GB0ffBsSPTdXn7nJNXrN/Hlzyb5Ws060vaFt4NKiCWLfhckA=="
        }
         */

        //日志
        $this->logger->addInfo('Yuntong payment callback data:', $returnData);

        $returnMsg = 'failure';

        $order_no = $getDatas['orderId'] ?? '';
        if (empty($order_no)) {
            $order_no = $args['orderId'] ?? '';
        }

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = Yuntong::getInstance($conf)->callbackVerifySign($returnData);

                            // {
                            //     "mchId": "1302180400011",
                            //     "orderId": "201805171757547865754",
                            //     "payTime": "20180517175918",
                            //     "openId": "2088112584510315",
                            //     "settlementPeriod": "T1",
                            //     "advanceFee": 0,
                            //     "prodId": "Z2",
                            //     "feeType": "CNY",
                            //     "rootOrderId": "2018051721001004310570723882",
                            //     "tradeState": "SUCCESS",
                            //     "accountId": "yan***@163.com",
                            //     "totalAmount": 1,
                            //     "actualPayAmount": 1,
                            //     "orgNo": "13020052",
                            //     "payChannel": "ALIPAY",
                            //     "settlementAmount": 0,
                            //     "tradeId": 100000000000000496,
                            //     "tradeType": "H5_WAP"
                            // }
                            
                            $money = sprintf("%.2f", $verify['totalAmount'] / 100);

                            if ($verify && $verify['orderId'] == $order_no && $pay->money == $money) {

                                $status = 1;
                                if ($verify['tradeState'] == 'SUCCESS') {
                                    $status = 0;
                                }

                                $pay->vendor_order_no = $verify['tradeId'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s', strtotime($verify['payTime']));
                                $pay->remark          = json_encode($verify);

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
                                            $this->logger->addInfo('Yuntong payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                        }
                                    }
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = 'success';
                                }
                            } else {
                                $returnMsg = 'verify sign error';
                            }
                        }
                    }
                } else {
                    $returnMsg = 'orderId error';
                }
            }
        }
        $response->getBody()->write($returnMsg);
        return $response->withStatus(200);
    }

    //同步通知
    public function notify(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();
        if ($request->isPost()) {
            $returnData = $request->getParsedBody();
        }

        // ?orgNo=13020052&mchId=1302180400011&tradeId=100000000000000510&orderId=201805181106065080606&prodId=Z2&payChannel=ALIPAY&tradeType=H5_WAP&totalAmount=1&tradeState=SUCCESS

        // 日志
        $this->logger->addInfo('Yuntong payment notify data:', $returnData);

        $status = 1;

        if ($returnData['tradeState'] == 'SUCCESS') {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';
            $money    = 0;

            if (isset($returnData['orderId']) && $returnData['orderId']) {

                $order_no = $returnData['orderId'];
                $money    = sprintf("%.2f", $returnData['totalAmount'] / 100);

            }

            if ($order_no) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->company_id) {

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($company) {

                            if ($company->url) {

                                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

                            } else {

                                $response->getBody()->write('Url is blank, Please contact webmaster.');
                            }
                        }
                    }
                } else {
                    $response->getBody()->write('orderId or money error');
                }
            }

        } else {

            $response->getBody()->write('failure');
        }

        return $response;
    }

}
