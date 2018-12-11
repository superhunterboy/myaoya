<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

class Duobao
{
    private $gateway         = 'https://transfer.169.cc/interface/transfer/index.aspx';
    private $queryUrl        = 'https://transfer.169.cc/interface/transfer/query.aspx';
    private $balanceQueryUrl = 'https://transfer.169.cc/interface/transfer/balance.aspx';

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
        $this->type = $type;
        $this->merOrderId = $payInfo['orderNo'] ?? '';

        if ($this->type == 'payment') {
            $this->params = [
                'parter' => $this->merchantId,
                'orderid' => $this->merOrderId,
                'value' => $payInfo['tranAmt'] / 100, // 分转元
                'payeebank' => $payInfo['bankName'],
                'account' => $payInfo['acctId'],
                'cardname' => $payInfo['acctName'],
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'settleid' => $this->merOrderId,
                'parter' => $this->merchantId,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'random' => md5(uniqid('', true)),
                'parter' => $this->merchantId,
            ];
        }

        $this->params['sign'] = $this->getSignStr($this->params);

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
            'verify'      => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnJson = json_decode($returnStr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 查询、余额查询
                if ($this->verifySign($returnJson)) {
                    return $returnJson;
                }
            } else {
                // 下单 success&settleid=结算序号&orderid=商户代付的订单号&msg=提现请求已经提交成功 或者 error&失败原因
                if (strpos($returnStr, 'success') !== false) {
                    $tmpStr = substr($returnStr, 8);
                    parse_str($tmpStr, $result);
                    return array_merge(['status' => substr($returnStr, 0, 7)], $result);
                } elseif (strpos($returnStr, 'error') !== false) {
                    $tmpArr = explode('&', $returnStr);
                    return [
                        'status' => $tmpArr[0],
                        'msg' => $tmpArr[1],
                    ];
                }
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $sign = $params['sign'];
        if ($this->type == 'query') {
            // 代付查询验签 parter={0}&settleid={1}&status={2}&amount={3}&key={4}
            return  $sign == strtolower(md5('parter=' . $params['parter'] . '&settleid=' . $params['settleid'] . '&status=' . $params['status'] . '&amount=' . $params['amount'] . '&key=' . $this->parterKey));
        } elseif ($this->type == 'balanceQuery') {
            // 查询余额验签 parter={0}&status={1}&enableAmt={2}&unpayment={3}&freeze={4}&key={5}
            return  $sign == strtolower(md5('parter=' . $params['parter'] . '&status=' . $params['status'] . '&enableAmt=' . $params['enableAmt'] . '&unpayment=' . $params['unpayment'] . '&freeze=' . $params['freeze'] . '&key=' . $this->parterKey));
        }
        return false;
    }

    private function getSignStr($params = [])
    {
        $signStr = '';
        foreach ($params as $k => $v) {
            $signStr .= $k . '=' . $v . '&';
        }
        return strtolower(md5(rtrim($signStr, '&') . $this->parterKey));
    }
}
