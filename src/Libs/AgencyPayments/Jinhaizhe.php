<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Jinhaizhe
{
    private $gateway         = 'http://payment.szjhzxxkj.com/payment/api_pay_single';
    private $queryUrl        = 'http://payment.szjhzxxkj.com/payment/api_pay_single_query';
    private $balanceQueryUrl = 'http://zf.szjhzxxkj.com/own/business/accountQuery';
    private $version         = '1.0';
    private $merchant_no;
    private $request_no;
    private $amount;
    private $amount_type  = 'CNY';
    private $product_type = '11002'; // 11001 余额代付、11002 额度代付
    private $acct_name;
    private $acct_id;
    private $acct_type = '21001'; // 21001 借记卡、21002 贷记卡
    private $mobile;
    private $bank_name;
    private $bank_settle_no;
    private $bank_branch_name;
    private $business_type = '20101';
    private $business_no;
    private $memo = '提现';

    private $pubkey; // 公钥
    private $prikey; // 私钥
    private $md5key; // md5签名key

    private $sp_request_no;
    private $sy_request_no;
    private $sp_reqtime;

    private $params = [];
    private $plaintext;
    private $type;

    private static $instance = null;

    private function __construct($config)
    {
        $this->client      = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $parterKey         = json_decode($config['parterKey'], true);
        $this->merchant_no = $config['parterNo'];
        $this->pubkey      = $parterKey['pubKey'];
        $this->prikey      = $parterKey['priKey'];
        $this->md5key      = $parterKey['saltKey'];
    }

    private function __clone()
    {
    }

    public static function getInstance($config = [])
    {
        if (is_null(self::$instance) || isset(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function generateSignature($payInfo = [], $type = 'payment')
    {
        $this->type          = $type;
        $this->request_no    = $payInfo['orderNo'];
        $this->sp_request_no = $payInfo['orderNo'];

        if ($this->type == 'payment') {
            $this->params = [
                'request_no'    => $this->request_no,
                'amount'        => $payInfo['tranAmt'],
                'amount_type'   => $this->amount_type,
                'product_type'  => $this->product_type,
                'acct_name'     => $payInfo['acctName'],
                'acct_id'       => $payInfo['acctId'],
                'acct_type'     => $this->acct_type,
                // 'mobile'        => $payInfo['mobile'],
                'bank_name'     => $payInfo['bankName'],
                'business_type' => $this->business_type,
                'memo'          => $this->memo,
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'sp_request_no' => $this->sp_request_no,
                // 'sy_request_no' => $this->sy_request_no,
                'sp_reqtime'    => date('YmdHis'),
            ];
        }

        if ($this->type == 'balanceQuery') {
            // 余额查询比较特殊，本应该在代付文档里，结果在支付文档里
            $this->params = [
                'merchantNo' => $this->merchant_no,
                'requestNo'  => $this->request_no,
            ];

            $signStr         = $this->params['merchantNo'] . "|" . $this->params['requestNo'];
            $this->plaintext = $signStr;
        } else {
            // type=query 以下字段共用
            $this->params['version']     = $this->version;
            $this->params['merchant_no'] = $this->merchant_no;

            $signStr         = Utils::getSignatureString($this->params);
            $this->plaintext = $signStr . 'sign=' . strtolower(md5($signStr . 'key=' . $this->md5key));
        }

        $this->params['sign'] = Utils::rsaSign_string($this->plaintext, $this->prikey);

        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        if ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('POST', $url, [
            'form_params' => $this->params,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnXml = $response->getBody()->getContents();
            if ($returnXml) {
                $returnArr = json_decode($returnXml, true);
                if ($this->type == 'balanceQuery') {
                    // 余额查询比较特殊，本应该在代付文档里，结果在支付文档里
                    return $returnArr;
                } else {
                    if ($returnArr['retCode'] == '12000') {
                        $returnStr = Utils::deCryptByRSA(urldecode($returnArr['cipher_data']), $this->prikey);
                        /**
                         * 下单：
                         * Array
                         *   (
                         *       [acct_id] => 6214837578067963
                         *       [acct_name] => 黄泽界
                         *       [amount] => 1
                         *       [code] => 3 // 1-处理中 2-处理成功 3-处理失败 4-已退汇
                         *       [memo] => 代付测试
                         *       [merchantNo] => 500009070528
                         *       [msg] => 代付失败
                         *       [product_type] => 11002
                         *       [requestNo] => 20170930141619
                         *       [sign] => dff74e048b1cbc689ddb9b8b8995bf6c
                         *   )
                         *
                         * 查询：
                         * Array
                         *   (
                         *       [acct_id] => 6214837578067963
                         *       [acct_name] => 黄泽界
                         *       [amount] => 1
                         *       [bank_branch_name] =>
                         *       [bank_name] => 招商银行
                         *       [bank_settle_no] =>
                         *       [business_no] => 0
                         *       [cur_type] => CNY
                         *       [memo] => 代付测试
                         *       [merchant_no] => 500009070528
                         *       [mobile] => 13538765336
                         *       [request_no] => 20170930141619
                         *       [sign] => 9f788bba71cd6732d3199cfd9035702e
                         *       [status] => 3 // 1-处理中 2-处理成功 3-处理失败 4-已退汇
                         *       [sy_request_no] => 150675217899783169
                         *   )
                         */
                        $params = json_decode($returnStr, true);
                        if (Utils::verifySignature($params, $this->md5key)) {
                            return $params;
                        }
                        return null;
                    }
                    // 非12000的情况，都按处理中处理，真实处理结果以反查接口返回的结果为准
                    return ['status' => 1, 'sy_request_no' => '', 'origData' => $returnArr];
                }
            }
            return null;
        }
        return null;
    }
}
