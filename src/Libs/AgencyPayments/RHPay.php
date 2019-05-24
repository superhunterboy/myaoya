<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

//23
class RHPay
{
    private $gateway         = 'http://119.23.32.180/pay-mer-api/api/pay/proxyIssue/1.0.0/';//交易类生产地址
    private $queryUrl        = 'http://119.23.32.180/pay-mer-api/api/query/proxyIssue/1.0.0/';
    private $balanceQueryUrl = 'http://119.23.32.180/pay-mer-api/api/balance/';

    private $client;
    private $type;
    private $agency_no; //商户号

    private $token; // token
    private $prikey; // 私钥
    private $postValue;

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->agency_no = $config['parterNo']; //商户号
        $parterKey    = json_decode($config['parterKey'], true);
        $this->token = $parterKey['token'];
        $this->prikey = $parterKey['priKey'];
    }

    public static function getInstance($config = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function generateSignature($payInfo = [], $type = 'payment')
    {
        $this->type = $type;
        $userName   = $payInfo['acctName'] ?? '';
        $bankName   = $payInfo['bankName'] ?? '';
        $orderNo    = $payInfo['orderNo'] ?? '';

        if ($this->type == 'payment') {
            $this->params['orderClientCode'] =$orderNo;
            $this->params['orderCountAmount'] =$payInfo['tranAmt'];
            $this->params['payProCode'] ='A1001';
            $this->params['payChnCode'] ='CH004';
            $this->params['orderPayCard'] =$payInfo['acctId'];
            $this->params['payeeType'] =1;
            $this->params['orderPayCardBankName'] =$bankName.'||';
            $this->params['orderPayName'] =$userName;
            $this->params['orderPayPhone'] ='13800138123';
            $this->params['orderPayIdcaType'] =1;
            $this->params['orderPayIdca'] ='110292843731733';
        } elseif ($this->type == 'query') {
            $this->params['orderCode'] =$payInfo['platform_order_no'];
            $this->params['orderClientCode'] =$orderNo;
            $this->params['orderCTime'] ='';
        } elseif ($this->type == 'balanceQuery') {
            $this->params['balanceTime'] =date('YmdHis');
        }
        $this->postValue = Utils::RHPay_encryptByRSA(json_encode($this->params), $this->prikey);
        //print_r($this->postValue);exit;
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
        $url .= $this->token;
        $response = $this->client->request('POST', $url, [
            'body'   => $this->postValue,
            'verify' => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);
            // 下单
            // {
            //  "status":"M0000",
            //  "msg":"通讯成功",
            //  "encryptStr":"响应密文",
            // }
            if (is_array($returnStr)) {
                $sRetValue = Utils::RHPay_decryptByRSA($returnStr['encryptStr'], $this->prikey);
                $aRetValue = json_decode($sRetValue, true);
                unset($returnStr['encryptStr']);
                $aRes = array_merge($returnStr,$aRetValue);
                return $aRes;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
    }

    private function getSignStr()
    {

    }
}
