<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

class Aifu
{
    private $gateway         = 'http://pay.ifeepay.com/withdraw/singleWithdraw';
    private $queryUrl        = 'http://pay.ifeepay.com/withdraw/queryOrder';
    private $balanceQueryUrl = 'http://pay.ifeepay.com/withdraw/queryBalance';

    private $payKey;
    private $md5Key; // md5签名key
    private $merchantNo;
    private $orderNo;

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client     = new Client([
            'http_errors' => false,
            'timeout' => 30
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
        $payKey        = '';
        $this->type    = $type;
        $this->orderNo = $payInfo['orderNo'];

        if ($this->type == 'payment') {
            $payKey       = $this->payKey;
            $this->params = [
                'merchant_no'  => $this->merchantNo,
                'order_no'     => $this->orderNo,
                'card_no'      => $payInfo['acctId'],
                'account_name' => base64_encode($payInfo['acctName']),
                'bank_branch'  => base64_encode($payInfo['branch']),
                'cnaps_no'     => '',
                'bank_code'    => $payInfo['bankCode'] ?? 'UNKNOWN',
                'bank_name'    => base64_encode($payInfo['bankName']),
                'amount'       => $payInfo['tranAmt'] / 100, // 分转元
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'merchant_no' => $this->merchantNo,
                'order_no'    => $this->orderNo,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'merchant_no' => $this->merchantNo,
            ];
        }

        $this->params['sign'] = $this->getSignStr($this->params, $payKey, $this->md5Key);

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
        $response = $this->client->request('GET', $url, [
            'query'  => $this->params,
            'verify' => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            // 下发
            // {
            //     "result_code":"000000",
            //     "result_msg":"代付申请成功，请耐心等待出款结果",
            //     "merchant_no":"144801006875",
            //     "order_no":"201801262047547023",
            //     "mer_order_no":"20180126124754",
            //     "result":"H",
            //     "sign":"ee00fb647359c1b77dfaf7fbe1d36c40"
            // }
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
            // 余额查询
            // {
            //     "result_code":"000000",
            //     "result_msg":"商户钱包余额查询成功",
            //     "merchant_no":"144801006875",
            //     "balance":"15476",
            //     "sign":"c82855395505745a47f7b7b1d6301950"
            // }
            $returnStr = json_decode($returnStr, true);
            if (isset($returnStr['sign'])) {
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
