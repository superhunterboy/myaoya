<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

//18
class Qingying
{
    private $gateway         = 'http://df.superway.info:11088/webservice/order'; // 生产环境下单地址
    private $queryUrl        = 'http://df.superway.info:11088/webservice/order'; // 查询接口地址
    private $balanceQueryUrl = 'http://df.superway.info:11088/webservice/order'; // 查询余额地址

    private $parterKey; //秘钥
    private $txcode; //交易代码
    private $txdate; //交易日期
    private $txtime; //交易时间
    private $version  = '2.0.0'; //版本号
    private $field003 = '000000'; //处理码
    private $field004; //代付金额
    private $field011 = '000000'; //流水号
    private $field031 = '0'; //到账类别
    private $field039; //应答码
    private $field041; //客户号
    private $field042; //商户号
    private $field048; //商户代付订单号
    private $field055; //收款方信息
    private $field062; //平台代付订单号
    private $field124; //响应信息
    private $field125; //SESSION
    private $field128; //签名

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->field042 = $config['parterNo']; //商户号
        $keyArr         = json_decode($config['parterKey'], true); // {"field041":"","parterKey":"","field031":""}
        if (is_array($keyArr)) {
            $this->field041  = $keyArr['field041']; //客户号
            $this->parterKey = $keyArr['parterKey'];
            $this->field031  = $keyArr['field031'] ?? 0; // 当日
        }
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
        $this->type = $type;
        $userName   = $payInfo['acctName'] ?? '';
        $bankNo     = $payInfo['acctId'] ?? '';
        $bankName   = $payInfo['bankName'] ?? '';
        $orderNo    = $payInfo['orderNo'] ?? '';
        $unionpayNo = $payInfo['unionpayNo'] ?? '';
        $mobile     = $payInfo['mobile'] ?? '';
        $userInfo   = "{$userName}|{$bankNo}|{$bankName}|{$unionpayNo}|{$mobile}|";

        if ($this->type == 'payment') {
            $this->params = [
                'txcode'   => 'F60007', //交易代码
                'txdate'   => date('Ymd'), //交易日期
                'txtime'   => date('His'), //交易时间
                'version'  => $this->version, //版本号
                'field003' => $this->field003, //处理码
                'field004' => strval($payInfo['tranAmt']), //代付金额
                'field011' => $this->field011, //流水号
                'field031' => $this->field031, //到账类别
                //'field039' => $this->field039, //应答码
                'field041' => $this->field041, //客户号
                'field042' => $this->field042, //商户号
                'field048' => $orderNo, //商户代付订单号
                'field055' => $userInfo, // mb_convert_encoding($userInfo, 'utf-8', 'GBK'), //收款方信息
                //'field062' => $this->field062, //平台代付订单号
                //'field124' => $this->field124, //响应信息
                'field125' => $this->field042 . microtime(true) * 10000, //SESSION
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'txcode'   => 'F60008', //交易代码
                'txdate'   => date('Ymd'), //交易日期
                'txtime'   => date('His'), //交易时间
                'version'  => $this->version, //版本号
                'field003' => $this->field003, //处理码
                'field011' => date('md') . mt_rand(10, 99), //流水号
                'field041' => $this->field041, //客户号
                'field042' => $this->field042, //商户号
                'field048' => substr($orderNo, 0, -1), //平台代付订单号 平台订单号少一位
                'field125' => $this->field042 . microtime(true) * 10000, //SESSION
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'txcode'   => 'F60012', //交易代码
                'txdate'   => date('Ymd'), //交易日期
                'txtime'   => date('His'), //交易时间
                'version'  => $this->version, //版本号
                'field003' => '000000', //处理码
                // 'field011' => date('md') . mt_rand(10, 99), //流水号
                'field041' => $this->field041, //客户号
                'field042' => $this->field042, //商户号
                'field062' => $orderNo, //平台代付订单号
                'field125' => $this->field042 . microtime(true) * 10000, //SESSION
            ];
        }

        //签名
        $this->params['field128'] = $this->getSignStr($this->params);
        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        // if ($this->type == 'query') {
        //     $url = $this->queryUrl;
        // } elseif ($this->type == 'balanceQuery') {
        //     $url = $this->balanceQueryUrl;
        // }
        // echo $url ,' <==> ', json_encode($this->params, JSON_UNESCAPED_UNICODE);
        // exit;
        $response = $this->client->request('POST', $url, [
            'json'   => $this->params,
            'verify' => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);

            if (is_array($returnStr)) {
                return $returnStr;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
    }

    private function getSignStr($params = [])
    {
        $temp = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $temp .= $value;
            }
        }
        $temp .= $this->parterKey;
        $tmp = mb_convert_encoding($temp, "GBK", "utf-8");

        return substr(strtoupper(md5($tmp)), 0, 16);
    }
}
