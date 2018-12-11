<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Tianfubao;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class TianfubaoController extends BaseController
{
    // 异步回掉
    public function notify(Request $request, Response $response, $args)
    {
        $returnData = [];
        // Fuck，这个支付返回的字符编码真 TM 变态，Fuck，shit！！！
        $getDatas = $request->getUri()->getQuery();
        echo iconv('gbk', 'utf-8', urldecode($getDatas));
        parse_str(iconv('gbk', 'utf-8', urldecode($getDatas)), $returnData);
        if ($request->isPost()) {
            $postDatas = file_get_contents('php://input');
            parse_str(iconv('gbk', 'utf-8', $postDatas), $returnData);
        }

        $isNetBank  = false;
        $cipherData = $returnData['cipher_data'] ?? null;
        $retcode    = $returnData['retcode'] ?? null;
        $retmsg     = $returnData['retmsg'] ?? null;

        // 银行返回来的数据解密出来的鬼东西
        if ($cipherData) {
            $isNetBank = true;
            $returnStr = Utils::tfbDeCryptNoFormat(str_replace(' ', '+', $cipherData), file_get_contents(__DIR__ . '/../../certs/tianfubao_rsa_private_key.pem'));
            if ($returnStr) {
                parse_str($returnStr, $params);
                $params['retcode'] = $retcode;
                $params['retmsg']  = $retmsg;
                $returnData        = $params;
            }
        }

        // 日志
        $this->logger->addInfo('Tianfubao payment notify data:', $returnData);

        $order_no = $returnData['sp_billno'] ?? $returnData['spbillno'];
        $money    = $returnData['tran_amt'] ?? $returnData['money'];
        $money    = sprintf("%.2f", $money / 100);

        /**
         *   array (
         *     'item_attach' => '',
         *     'item_name' => '',
         *     'listid' => '1021800776625170906000077606',
         *     'notify_type' => '1',
         *     'pay_type' => '800201',
         *     'retcode' => '00',
         *     'retmsg' => '',
         *     'sign' => 'bd062a5625f8ed71978e065f3dc2df17',
         *     'sign_type' => 'MD5',
         *     'sp_billno' => '20170906190251',
         *     'sysd_time' => '20170906190506',
         *     'tran_amt' => '1',
         *     'tran_state' => '1',
         *     'tran_time' => '20170906190344',
         *   )
         */

        if ($order_no) {

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

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = 'FAIL';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 14) {

                                $verify = Tianfubao::getInstance($conf)->verifySign($returnData);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($returnData['retcode'] == '00' && ((isset($returnData['tran_state']) && $returnData['tran_state'] == '1') || (isset($returnData['result']) && $returnData['result'] == '1'))) {

                                    $status = 0;

                                }
                                $time                 = isset($returnData['tran_time']) ? date('Y-m-d H:i:s', strtotime($returnData['tran_time'])) : date('Y-m-d H:i:s');
                                $pay->vendor_order_no = $returnData['listid'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = $time;
                                $pay->remark          = json_encode($returnData);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 14) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => $time,
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
                                                $this->logger->addInfo('Tianfubao payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'SUCCESS';

                                        if ($isNetBank) {
                                            $returnMsg = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root>\n<retcode>00</retcode>\n</root>\n";
                                        }
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
    public function callback(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        $this->logger->addInfo('Tianfubao payment callback data:', $getDatas);

        return $response;
    }
}
