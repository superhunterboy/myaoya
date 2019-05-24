<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Gft
{
    private $gateway         = 'http://agent.51gftpay.com/gateway/api/lwithdrawal';
    private $queryUrl        = 'http://agent.51gftpay.com/gateway/api/orderQuery';
    private $balanceQueryUrl = 'http://agent.51gftpay.com/gateway/api/balanceQuery';

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

        //$this->callbackUrl     = $config['callbackUrl'];
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

            $this->params['version']            ="V001";
            $this->params['timestamp']          =time();
            $this->params['mchId']              =$this->merchantNo;
            $this->params['amount']             =$payInfo['tranAmt'];
            $this->params['mchOrderNo']         =$this->orderNo;
            $this->params['cardholderName']     =$payInfo['acctName'];
            $this->params['backNotifyUrl']      ="http://baidu.com";
            $this->params['withdrawalsType']    ="102";
            $this->params['bankCardNo']         =$payInfo['acctId'];
            $this->params['bankName']           =$payInfo['bankName'];
            $this->params['password']           =md5('123qwe');
            $this->params['sign']               =$this->getSignStr($this->params,$this->payKey);
        } elseif ($this->type == 'query') {
            $this->params['mchId']              =$this->merchantNo;
            $this->params['type']               =2;
            $this->params['version']            ="V001";
            $this->params['mchOrderNo']         =$this->orderNo;
            $this->params['timestamp']          =time();
            $this->params['sign']               =$this->getSignStr($this->params,$this->payKey);
        } elseif ($this->type == 'balanceQuery') {
            $this->params['version']            ="V001";
            $this->params['timestamp']          =time();
            $this->params['mchId']              =$this->merchantNo;
            $this->params['sign']=$this->getSignStr($this->params,$this->payKey);
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
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_SSLVERSION, 1 );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
            'Content-type:application/json;charset=UTF-8'
        ));
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ($this->params, JSON_UNESCAPED_UNICODE ) );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $resp = curl_exec ( $ch );
        $result=json_decode($resp, true);
        if($this->type == 'payment') {   /* 提款返回信息 */
            $returnStr = $result;
            if (isset($returnStr['sign'])) {
                if ($this->verifySign($returnStr)) {
                    return $result;
                } else {
                     return [
                        "result_code" => 'PAR005',
                        "result_msg" => '返回数据签名验证失败',
                     ];
                }
            }
            return $returnStr;
        }elseif($this->type == 'balanceQuery'){   /* 提款返回信息 */
            if ($result['code'] == '200') {
                return $result;
            }
        }elseif($this->type == 'query'){

            return $result;
        }
        return null;
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];

        unset($params['sign']);
        $returnsign=$this->getSignStr($params,$this->parterKey);
        return strtoupper($sign) == $returnsign;
    }


    /*
     * 签名
     */
    private function getSignStr($array = [], $paysecret = '')
    {
        ksort($array);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg = $msg . "$key=$val&";
        }
        $msg = $msg ."key=".$paysecret;
        return strtoupper(md5($msg));
    }

}
