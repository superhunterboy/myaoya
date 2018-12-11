<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Gaiya
{
    private $gateway         = 'http://api.gaia58.com/resolve/daifu';
    private $queryUrl        = 'http://api.gaia58.com/resolve/order/status';
    private $balanceQueryUrl = 'http://api.gaia58.com/resolve/balance';

    private $payKey;
    private $md5Key; // md5签名key
    private $merchantNo;
    private $orderNo;
    private $shopname="abc";

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client     = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->merchantNo = $config['parterNo'];
        $this->payKey     = $config['parterKey'];
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
        $this->type    = $type;
        $this->orderNo = $payInfo['orderNo'];

        if ($this->type == 'payment') {

            $this->params['sendTime']      =date('YmdHis');
            $this->params['merchantId']    =$this->merchantNo;
            $this->params['merOrderId']    =$this->orderNo;
            $this->params['accNo']         =$payInfo['acctId'];
            $this->params['customerNm']    =$payInfo['acctName'];
            $this->params['bankId']        =$payInfo['bankCode'];
            $this->params['txnAmt']        =$payInfo['tranAmt'];
            $this->params['backUrl']       ="http://47.90.98.83:9898";
            $this->params['subject']       ='abc'.time().rand(10000,99999);
            $this->params['body']          ='def'.time().rand(10000,99999);
            $this->params['ppFlag']        ='01';
            $this->params['gateway']       ='daifu';
            $this->params['signature']=$this->getSignStr($this->params,$this->payKey);
            $this->params['subject']=base64_encode($this->params['subject']);
            $this->params['body']=base64_encode($this->params['body']);
            $this->params['signMethod']    ='MD5';
        } elseif ($this->type == 'query') {
            $this->params = [
                'sendTime'      => date('YmdHis'),
                'merchantId'    => $this->merchantNo,
                'merOrderId'    => $this->orderNo
            ];
            $this->params['signature']=$this->getSignStr($this->params,$this->payKey);
            $this->params['signMethod']    ='MD5';

        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'sendTime'      =>date('YmdHis'),
                'merchantId'    => $this->merchantNo
            ];
            $this->params['signature']=$this->getSignStr($this->params,$this->payKey);
            $this->params['signMethod']    ='MD5';
        }

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
            'form_params'  => $this->params,
            'verify' => false,
        ]);

        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);

            if (isset($returnStr['signature'])) {
                if ($this->verifySign($returnStr)) {
                    return $returnStr;
                } else {
                    return [
                        "result_code" => 'PAR005',
                        "result_msg" => '返回数据签名验证失败',
                    ];
                }
            }
            return $returnStr;
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $sign = $params['signature'];
        $returnsign=$this->getSignStr($params,$this->payKey);

        return $sign == $returnsign;
    }
    //签名
    private function getSignStr($array = [], $payKey = '', $md5key = '')
    {
        ksort($array);
        $msg = "";
        $i = 0;
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            // 不参与签名
            if ($val != null && $key !== "signMethod" && $key !== "signature") {
                if ($i == 0) {
                    $msg = $msg . "$key=$val";
                } else {
                    $msg = $msg . "&$key=$val";
                }
                $i++;
            }
        }
        $msg = $msg . $payKey;
        return base64_encode(md5($msg));
    }
}
