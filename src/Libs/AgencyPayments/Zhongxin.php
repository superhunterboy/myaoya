<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use \Weiming\Models\Withdrawal;

class Zhongxin
{
    private $gateway         = 'http://api.7krh.com/withdraw/gateway'; // 生产环境下单地址
    private $queryUrl        = 'http://api.7krh.com/withdraw/query'; // 查询接口地址
    private $balanceQueryUrl = 'http://api.7krh.com/withdraw/balance'; // 查询余额地址

    private $md5Key; // md5签名key
    private $merchantNo;

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
        $this->orderNo = $payInfo['orderNo'];
        if ($this->type == 'payment') {
            $this->params = [
                'version'      => 'V1.0', // 接口版本
                'partner_id'   => $this->merchantNo, // 商户编号
                'order_no'     => $this->orderNo, // 订单号
                'amount'       => $payInfo['tranAmt'], // 交易金额
                'bank_code'    => $payInfo['bankCode'], // 银行代码
                'account_no'   => $payInfo['acctId'], // 开户人卡号
                'account_name' => $payInfo['acctName'], // 开户人姓名
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'version'    => 'V1.0', // 接口版本
                'partner_id' => $this->merchantNo, // 商户编号
                'order_no'   => $payInfo['orderNo'], // 订单号
                'trade_no'   => $payInfo['tradeNo'], // 系统流水号
                'amount'     => $payInfo['tranAmt'], // 交易金额
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'version'    => 'V1.0', // 接口版本
                'partner_id' => $this->merchantNo, // 商户编号
            ];
        }

        $this->params['sign'] = $this->getSignStr($this->params, $this->md5Key);

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

        $client = new Client();
        $res    = $client->request('POST', $url, ['form_params' => $this->params]);
        if ($res->getStatusCode() == '200') {
            $returnStr = $res->getBody()->getContents();
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

    private function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        return $this->getSignStr($params,$this->md5Key);
        // $jsonStr = json_encode($params, JSON_UNESCAPED_UNICODE);
        // return $sign == md5($jsonStr . $this->md5Key);
    }

    private function getSignStr($params = [], $md5key = '')
    {
        $sign = '';
        ksort($params, SORT_STRING);
        foreach ($params as $key => $val) {
            $sign .= $key . '=' . $val . '&';
        }
        $sign .= $md5key;
        return strtolower(md5($sign));
    }
}
