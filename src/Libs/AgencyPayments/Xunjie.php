<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Xunjie
{
    private $gateway         = 'http://pay88.cat39.com/transck/trans/api/back.json'; // 'http://pay88.cat39.com/trans/trans/api/back.json'; // 8001、8002是ck的
    private $queryUrl        = 'http://pay88.cat39.com/trans/trans/api/back.json';
    private $balanceQueryUrl = 'http://pay88.cat39.com/trans/trans/api/back.json';

    private $parterKey;
    private $pubKey;
    private $priKey;
    private $channel;
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
        $keyArr           = json_decode($config['parterKey'], true); // {"channel":"8001","pubKey":"...","priKey":"..."}
        if (is_array($keyArr)) {
            $this->pubKey = $keyArr['pubKey'];
            $this->priKey = $keyArr['priKey'];
            $this->channel = $keyArr['channel'] ?? '8002'; // 当日
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
        $this->type       = $type;
        $this->merOrderId = $payInfo['orderNo'] ?? '';

        if ($this->type == 'payment') {
            $this->params = [
                'version'       => '1.0.0',
                'transType'     => 'PROXY_PAY',
                'productId'     => (isset($payInfo['channel']) && in_array($payInfo['channel'], ['8001', '8002'])) ? $payInfo['channel'] : $this->channel, // 默认 8002 (当日), // 当日D0 次日T1，8002 D0代付、8001 T1代付
                'merNo'         => $this->merchantId,
                'orderDate'     => date('Ymd'),
                'orderNo'       => $this->merOrderId,
                'notifyUrl'     => '', // 'http://47.90.98.83:9898/adm/index.html',
                'transAmt'      => $payInfo['tranAmt'], // 分
                'commodityName' => '提现',
                // 'phoneNo'       => $payInfo['mobile'] ?? '',
                'cardNo'        => $payInfo['acctId'],
                'cardName'      => $payInfo['acctName'],
                'cerdId'        => '',
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'version'    => '1.0.0',
                'transType'  => 'TRANS_QUERY',
                'productId'  => '0000',
                'merNo'      => $this->merchantId,
                // 'serialId'   => '',
                'orderId'    => $this->merOrderId,
                'notifyFlag' => '',
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'version'   => '1.0.0',
                'transType' => 'BALANCE_QUERY',
                'productId' => '0000',
                'merNo'     => $this->merchantId,
            ];
        }

        $temp = $this->getSignStr($this->params);

        $this->params['signature'] = Utils::rsaSign_string($temp, $this->priKey);

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
            $returnStr = json_decode($returnStr, true);
            // 下单
            // Array
            // (
            //     [commodityName] => 提现
            //     [merNo] => 850510059375264
            //     [orderDate] => 20180403
            //     [orderNo] => 20180403173004
            //     [productId] => 8002
            //     [respCode] => T042
            //     [respDesc] => 余额不足
            //     [signature] => CEkJUlU93bpV57He9WnWAFlRZ3Zh9fzkjCYiNY9XYhnLTm0lKOJRWUIZ1GtQxp5A4kE0lHrXKLBvY4HwLitmo7mteDRYm6GP3kO3cjv0MAlrPUq8AxwqgFE/vVm3eGnBQlStCPCC1x8qHpEcsYl1eMArS0Fxpvzu19abiD5St0yn6gsynObruwRx4dANEhDZ583ixXNRbGIT8qpGlHY+Xro4WuUm7RFHRYkUSL5uTdebZcL/cj5AtF+6VGEy+78+ITWYiuQBQkhAnw03UgJHrO1/5d9kzhSSq8Wy4B1Crh6u0dcWduVYx59HFKkUmGM0bY3UX2uKq0uSmdEWBdKdGw==
            //     [transAmt] => 1
            //     [transType] => PROXY_PAY
            //     [version] => 1.0.0
            // )

            // 查询
            // Array
            // (
            //     [merNo] => 850510059375264
            //     [oRespCode] => T042
            //     [oRespDesc] => 余额不足
            //     [orderDate] => 20180403
            //     [orderNo] => 20180403171225
            //     [productId] => 8002
            //     [respCode] => 0000
            //     [respDesc] => 交易成功
            //     [signature] => oSm+Yh5CTtWnhQln4zqjqq8JfTrDbNH9lw7eeDkVIoJQMgvZn7LMrkJEQqjhUQ7byOivi7FwGtIjrGi1E+EkHCG8+xekL2m5R+cG+FeQRnAyEHPLaVZfmBreoqYSt5l9UR38Tpa1QmkcRNUUB0xFtTEB7TfyYICOSKackOEFTPszlmd0HofWbVj2zI8YNh+Hlw2rEYQCgsMQ9jRGlTOoJY3tGW3obXWEvBPrkUwMjAR+sCtK/MsKJgT9sivLLj29AXBIW7kFZAVmPX4N/xJTUa2JZeAadeGc4Y/qrCFkgb+z5BI7ldWLRIRf8WK2mMh+RciRrTPQQALARe2Gb2vS4A==
            //     [transAmt] => 1
            //     [transType] => PROXY_PAY
            //     [version] => 1.0.0
            // )

            // 余额查询
            // Array
            // (
            //     [cashAccount] => 1
            //     [cashDepositAccount] => 0
            //     [creditAccount] => 0
            //     [feeChargeAccount] => 0
            //     [merNo] => 850510059375264
            //     [productId] => 0000
            //     [respCode] => 0000
            //     [respDesc] => 交易成功
            //     [settlementAccount] => 0
            //     [signature] => U+BQFjI/M+MpIbi19h96/HW5YQmORhQw6XLXQVmsI1CRauRYvIZzzE223fIsyD9KdmYha8o3k/7jT5LRU/z0/FgSZ+IjwqE/bUHiMH9e4bC4r5j0pIhO6K7go3dYglwIahSxLMCH/0IhMLdo31O09fG7G4o9shqpm8+X6B41q82GNHlIS0lQITq/Ol9QEw7lvYsIRPOa1xABjT2YMG2Nd6R2xuyYuLjbnSEavpEMwXcwfOMSBTCUW7q8RtZMpTZDekR8P10GIf77P0gwLw9vFeXdGaqVyAesHUi7vDALhYoUEfrgGglxwgIom242gPRLI/zw0XQzq730KazA0F7mCA==
            //     [totalBalance] => 1
            //     [totalFreezeBalance] => 0
            //     [transType] => BALANCE_QUERY
            //     [version] => 1.0.0
            // )
            if ($this->verifySign($returnStr)) {
                return $returnStr;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $sign = '';
        if (isset($params['signature'])) {
            $sign = $params['signature'];
            unset($params['signature']);
        }
        $data = $this->getSignStr($params);
        return Utils::rsaVerify_string($data, $this->pubKey, $sign);
    }

    private function getSignStr($params = [])
    {
        ksort($params);
        $temp = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $temp .= $key . '=' . $value . '&';
            }
        }
        return substr($temp, 0, strlen($temp) - 1);
    }
}
