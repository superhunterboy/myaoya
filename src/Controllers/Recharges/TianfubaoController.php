<?php

namespace Weiming\Controllers\Recharges;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Tianfubao;
use \Weiming\Libs\Utils;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

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
        $this->logger->addInfo('Tianfubao recharge notify data:', $returnData);

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
                $recharge = Recharge::where('order_no', '=', $order_no)->where('amount', '=', $money)->first();
                if ($recharge) {
                    if ($recharge->platform_id) {
                        $platform = Platform::where('id', '=', $recharge->platform_id)->first();
                        if ($platform) {
                            $conf['parterNo']    = $platform->no;
                            $conf['parterKey']   = $platform->key;
                            $conf['callbackUrl'] = $platform->callback_url;
                            $conf['notifyUrl']   = $platform->notify_url;
                            $verify              = false;
                            $returnMsg           = 'FAIL';
                            $verify              = Tianfubao::getInstance($conf)->verifySign($returnData);
                            if ($verify) {
                                $status = 1;
                                if ($returnData['retcode'] == '00' && ((isset($returnData['tran_state']) && $returnData['tran_state'] == '1') || (isset($returnData['result']) && $returnData['result'] == '1'))) {
                                    $status = 0;
                                }
                                $recharge->platform_order_no = $returnData['listid'];
                                $recharge->status            = $status;
                                $recharge->remark            = json_encode($returnData);
                                $res                         = $recharge->save();
                                if ($res) {
                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);
                                    $returnMsg = 'SUCCESS';
                                    if ($isNetBank) {
                                        $returnMsg = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root>\n<retcode>00</retcode>\n</root>\n";
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

        $this->logger->addInfo('Tianfubao recharge callback data:', $getDatas);

        return $response;
    }
}
