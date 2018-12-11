<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Yafu
{
    private $gateway  = 'http://yf.yafupay.com/yfpay/cs/TX0001.ac';
    private $queryUrl = 'http://yf.yafupay.com/yfpay/cs/TX0002.ac';
    private $balanceQueryUrl = 'http://yf.yafupay.com/yfpay/cs/balance.ac';
    private $version  = '3.0';
    private $consumerNo;
    private $merOrderNo;
    private $transAmt;
    private $accountName;
    private $accountNo;
    private $acountBankNo;
    private $accountCountry;
    private $accountCity;
    private $sign;

    private $params = [];
    private $type;

    private static $_instance = null;

    private function __construct($config)
    {
        $this->client     = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->consumerNo = $config['parterNo'];
        $this->parterKey  = $config['parterKey'];
    }

    private function __clone()
    {
    }

    public static function getInstance($config = [])
    {
        if (is_null(self::$_instance) || isset(self::$_instance)) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }

    public function generateSignature($payInfo = [], $type = 'payment')
    {
        $this->type       = $type;
        $this->merOrderNo = $this->consumerNo . $payInfo['orderNo'];

        if ($this->type == 'payment') {
            $this->params = [
                'transAmt'       => $payInfo['tranAmt'],
                'accountName'    => $payInfo['acctName'],
                'accountNo'      => $payInfo['acctId'],
                'acountBankNo'   => $payInfo['bankName'],
                'accountCountry' => $payInfo['province'],
                'accountCity'    => $payInfo['city'],
            ];
        }

        if ($this->type != 'balanceQuery') {
            $this->params['merOrderNo'] = $this->merOrderNo;
        }

        // type=query 以下字段共用
        $this->params['version']    = '3.0';
        $this->params['consumerNo'] = $this->consumerNo;

        $this->sign = strtoupper(md5(Utils::getSignatureString($this->params) . 'key=' . $this->parterKey));

        $this->params['sign'] = $this->sign;

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
            $returnData = $response->getBody()->getContents();
            if ($returnData) {
                $returnData = json_decode($returnData, true);
                return $returnData;
            }
            return null;
        }
        return null;
    }
}
