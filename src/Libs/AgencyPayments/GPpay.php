<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

class GPpay
{
    private $gateway         = 'https://pay1.amazinglypay.com/withdraw/singleWithdraw'; // 生产环境下单地址
    private $queryUrl        = 'https://pay1.amazinglypay.com/withdraw/queryOrder'; // 查询接口地址
    private $balanceQueryUrl = 'https://pay1.amazinglypay.com/withdraw/queryBalance'; // 查询余额地址

    private $payKey;
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
        $parterKey        = json_decode($config['parterKey'], true);
        $this->merchantNo = $config['parterNo'];
        $this->md5Key     = $parterKey['md5Key'];
        $this->payKey     = $parterKey['payKey'];
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
        $payKey     = '';
        $this->type = $type;

        if ($this->type == 'payment') {
            $payKey        = $this->payKey;
            $this->orderNo = $payInfo['orderNo'];
            $this->params  = [
                'merchant_no'  => $this->merchantNo,
                'order_no'     => $payInfo['orderNo'],
                'card_no'      => $payInfo['acctId'],
                'account_name' => base64_encode($payInfo['acctName']),
                'bank_branch'  => $payInfo['bank_branch'] ?? '',
                'cnaps_no'     => $payInfo['cnaps_no'] ?? '',
                'bank_code'    => $payInfo['bankCode'] ?? '',
                'bank_name'    => base64_encode($payInfo['bankName']),
                'amount'       => sprintf("%.2f", $payInfo['tranAmt']), // 两位小数
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'merchant_no' => $this->merchantNo,
                'order_no'    => $payInfo['orderNo'],
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'merchant_no' => $this->merchantNo,
            ];
        }

        $this->params['sign'] = $this->getSignStr($this->params, $payKey, $this->md5Key);

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
        $url = $this->gateway;
        if ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('GET', $url, [
            'query'  => $this->params,
            'verify' => false,
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
        }
        return null;
    }

    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $jsonStr = json_encode($params, JSON_UNESCAPED_UNICODE);
        return $sign == md5($jsonStr . $this->md5Key);
    }

    private function getSignStr($params = [], $payKey = '', $md5key = '')
    {
        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key . '=' . $val . '&';
        }
        if ($payKey) {
            $sign .= 'pay_pwd=' . $payKey . '&key=' . $md5key;
        } else {
            $sign .= 'key=' . $md5key;
        }
        return md5($sign);
    }
}
