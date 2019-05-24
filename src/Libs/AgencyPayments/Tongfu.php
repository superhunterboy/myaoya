<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

class Tongfu
{
    private $gateway = 'https://gate.tongpays.com/Pay'; // 生产环境下单地址

    private $md5Key; // md5签名key
    private $merchantNo;
    private $orderNo;

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->merchantNo = $config['parterNo'];
        $this->md5Key     = $config['parterKey'];
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

        if ($this->type == 'payment') {
            $this->orderNo = $payInfo['orderNo'];
            $dataArr       = [
                array(
                    'fxddh'     => $payInfo['orderNo'],
                    'fxdate'    => date('YmdHis'),
                    'fxfee'     => $payInfo['tranAmt'],
                    'fxbody'    => $payInfo['acctId'],
                    'fxname'    => $payInfo['acctName'],
                    'fxaddress' => $payInfo['bankName'],
                    'fxzhihang' => '',
                    'fxsheng'   => '',
                    'fxshi'     => '',
                ),
            ];
            $this->params = [
                'fxid'        => $this->merchantNo,
                'fxaction'    => 'repay',
                'fxnotifyurl' => $payInfo['callbackUrl'],
                'fxbody'      => json_encode($dataArr),
            ];
        } elseif ($this->type == 'query') {
            $dataArr = [
                array(
                    'fxddh' => $payInfo['orderNo'],
                )
            ];
            $this->params = [
                'fxid'     => $this->merchantNo,
                "fxaction" => "repayquery", //查询动作
                "fxbody"   => json_encode($dataArr), //订单信息域 json字符串数据
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                "fxid"     => $this->merchantNo, //商户号
                "fxdate"   => date('YmdHis'), //查询时间
                "fxaction" => 'money', //商户号

            ];
        }
        $this->params['fxsign'] = $this->getSignStr($this->params, $this->md5Key);
        return $this;
    }

    // 下发
    // Array
    // (
    //     [result_code] => TRS001
    //     [result_msg] => 商户钱包余额不足
    // )
    // 查询
    // {
    //     "result_code":"000000",
    //     "result_msg":"代付订单信息查询成功",
    //     "merchant_no":"144801006875",
    //     "order_no":"20180126130623",
    //     "result":"H",
    //     "amount":"4",
    //     "withdraw_fee":"2",
    //     "sign":"358a52dd9d38f38b4f8577ae961d0555"
    // }
    // 查询余额
    // Array
    // (
    //     [result_code] => 000000
    //     [result_msg] => 商户钱包余额查询成功
    //     [merchant_no] => GP166901011645
    //     [balance] => 0
    //     [sign] => 8a2e3d9e2159207dd17edf9810807658
    // )
    public function sendRequest()
    {
        $response = $this->client->request('POST', $this->gateway, [
            'form_params' => $this->params,
            'verify'      => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);
            if (isset($returnStr['sign'])) {
                if ($this->verifySign($returnStr)) {
                    return $returnStr;
                } else {
                    return [
                        "result_code" => 'PAR005',
                        "result_msg"  => '返回数据签名验证失败',
                    ];
                }
            }
            return $returnStr;
        } else {
            $returnStr = $response->getBody()->getContents();
            return $returnStr;
        }
        return null;
    }

    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $mySign = $this->getSignStr($params, $this->md5Key);
        return $sign == $mySign;
    }

    private function getSignStr($params = [], $md5key = '')
    {
        $sign = '';
        if (isset($params['fxnotifyurl'])) {
            unset($params['fxnotifyurl']);
        }
        foreach ($params as $key => $val) {
            $sign .= $val;
        }
        $sign .= $md5key;
        $this->params['signStr'] = $sign;
        $this->params['md5key']  = $md5key;
        return md5($sign);
    }
}
