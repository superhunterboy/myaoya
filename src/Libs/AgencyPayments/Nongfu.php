<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

class Nongfu
{
    private $gateway         = 'http://epay.nongfupay.com/pay';
    private $queryUrl        = 'http://epay.nongfupay.com/order/status';
    private $balanceQueryUrl = 'http://epay.nongfupay.com/balance';

    private $parterKey; // md5签名key
    private $merchantId;
    private $merOrderId;

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client     = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->merchantId = $config['parterNo'];
        $this->parterKey  = $config['parterKey'];
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
        $this->type       = $type;
        $this->merOrderId = $payInfo['orderNo'] ?? '';

        if ($this->type == 'payment') {
            $this->params = [
                'version'    => '1.0.0',
                'txnType'    => '12',
                'txnSubType' => '01',
                'bizType'    => '000401',
                'accessType' => '0',
                'accessMode' => '01',
                'merchantId' => $this->merchantId,
                'merOrderId' => $this->merOrderId,
                'accNo'      => $payInfo['acctId'],
                'ppFlag'     => '01', // 00 对公、01 对私
                'customerNm' => $payInfo['acctName'],
                'issInsName' => '', // 对公必填
                'txnTime'    => date('YmdHis'),
                'txnAmt'     => $payInfo['tranAmt'], // 分
                'currency'   => 'CNY',
                'backUrl'    => '',
                'payType'    => '0401',
                'bankId'     => $payInfo['bankCode'],
                'subject'    => '提现',
                'body'       => '提现',
                'purpose'    => '',
                'merResv1'   => '',
                'gateway'    => 'daifu',
                'signMethod' => 'MD5',
                'signature'  => '',
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'merchantId' => $this->merchantId,
                'merOrderId' => $this->merOrderId,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'merchantId' => $this->merchantId,
            ];
        }

        $this->params['signature'] = $this->getSignStr($this->params);

        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        if ($this->type == 'payment') {
            $this->params['subject'] = base64_encode($this->params['subject']);
            $this->params['body']    = base64_encode($this->params['body']);
        } elseif ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('POST', $url, [
            'form_params' => $this->params,
            'verify'      => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);
            // 下单
            // Array
            // (
            //     [success] => 1 // 1 成功 0 失败
            //     [code] => 1001 // 1000 待支付 1001 支付成功 1111 交易进行中 1002 支付失败
            //     [msg] => 交易成功
            //     [timestamp] => 1517196569
            //     [signature] => SCOL8GLt2UCBU/5nZKpHNQ==
            // )
            // 查询
            // Array
            // (
            //     [success] => 1
            //     [code] =>
            //     [msg] =>
            //     [timestamp] => 1517196680
            //     [merchantId] => 910180123135430
            //     [merOrderId] => 20180129032935177
            //     [txnAmt] => 100
            //     [status] => 1001
            //     [statusDesc] => 交易成功
            //     [signature] => AL6cwytdTliyJHW6dKPdyQ==
            // )
            // )
            // 余额查询
            // Array
            // (
            //     [success] => 1
            //     [code] => 0
            //     [msg] =>
            //     [timestamp] => 1517191864
            //     [balance] => 0
            //     [merchantId] => 910180123135430
            //     [signature] => Rt8cdBBprZQ+f/QOqbVpQQ==
            // )
            if ($this->verifySign($returnStr)) {
                return $returnStr;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $signature = $params['signature'];
        unset($params['signature']);
        ksort($params);
        $uri = urldecode(http_build_query($params));
        $uri = $uri . $this->parterKey;
        return $signature == base64_encode(md5($uri, true));
    }

    private function getSignStr($params = [])
    {
        unset($params['signMethod']);
        unset($params['signature']);
        ksort($params);
        $uri = urldecode(http_build_query($params));
        $uri = $uri . $this->parterKey;
        return base64_encode(md5($uri, true));
    }
}
