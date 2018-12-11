<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;

class Zesheng
{
    private $gateway  = 'http://spayment.zsagepay.com/payment/payment.do';
    private $queryUrl = 'http://spayment.zsagepay.com/payment/queryState.do';
    private $balanceQueryUrl = 'http://spayment.zsagepay.com/payment/merBalance.do';
    private $merchantCode;
    private $nonceStr;
    private $outOrderId;
    private $totalAmount;
    private $intoCardNo;
    private $intoCardName;
    private $intoCardType = '2';
    private $bankCode;
    private $type = '04';
    private $bankName;
    private $remark = '';
    private $sign;
    private $md5key;

    private $params = [];
    private $optype;

    private static $instance = null;

    private function __construct($config)
    {
        $this->client       = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->merchantCode = $config['parterNo'];
        $this->md5key       = $config['parterKey'];
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

    public function generateSignature($payInfo = [], $optype = 'payment')
    {
        $this->optype     = $optype;
        $this->outOrderId = $payInfo['orderNo'];

        if ($this->optype == 'payment') {
            $this->params = [
                'totalAmount'  => $payInfo['tranAmt'],
                'intoCardNo'   => $payInfo['acctId'],
                'intoCardName' => $payInfo['acctName'],
                'intoCardType' => $this->intoCardType,
                'bankCode'     => $this->bankCode,
                'type'         => $this->type,
                'bankName'     => $payInfo['bankName'],
                'remark'       => $this->remark,
            ];
        }

        if ($this->optype != 'balanceQuery') {
            $this->params['outOrderId']   = $this->outOrderId;
        }

        // 公共字段
        $this->params['merchantCode'] = $this->merchantCode;
        $this->params['nonceStr']     = md5(uniqid('', true));

        $signStr              = $this->getSignatureString($this->params);
        $this->sign           = strtoupper(md5($signStr . 'KEY=' . $this->md5key));
        $this->params['sign'] = $this->sign;

        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        if ($this->optype == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->optype == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('POST', $url, [
            'form_params' => $this->params,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnXml = $response->getBody()->getContents();
            if ($returnXml) {
                $returnArr = json_decode($returnXml, true);
                if ($returnArr['code'] == '00') {
                    /**
                     * 下单：
                     *   {
                     *       "code": "00",
                     *       "data": {
                     *           "fee": 1,
                     *           "merchantCode": "1000000001",
                     *           "orderId": "2017102300024504043",
                     *           "outOrderId": "20171023093434",
                     *           "sign": "638077E855B34C507BB5E6F4257FB1D4",
                     *           "totalAmount": 100
                     *       },
                     *       "msg": "成功"
                     *   }
                     *
                     * 查询：
                     *   {
                     *       "code": "00",
                     *       "data": {
                     *           "errorMsg": "操作完成",
                     *           "fee": 1,
                     *           "merchantCode": "1000000001",
                     *           "orderId": "2017102300024504043",
                     *           "outOrderId": "20171023093434",
                     *           "sign": "F490F9DBE33809DC6499E4F1542726B2",
                     *           "state": "02",
                     *           "totalAmount": 100,
                     *           "transTime": "20171023094001"
                     *       },
                     *       "msg": "成功"
                     *   }
                     */
                    if ($this->verifySignature($returnArr['data'], $this->md5key)) {
                        return $returnArr['data'];
                    }
                    return null;
                }
                // 非 00 的情况，都按处理中处理，真实处理结果以反查接口返回的结果为准
                return ['state' => '01', 'orderId' => '', 'origData' => $returnArr];
            }
            return null;
        }
        return null;
    }

    /**
     * 得到待签名字符串
     * @param  array  $arr 签名数据
     * @return string
     */
    private function getSignatureString($arr)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "remark" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return $signPars;
    }

    /**
     * 签名验证
     * @param  array  $arr 接口返回参数
     * @param  string  $key 商户key
     * @return boolean
     */
    private function verifySignature($arr, $key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "errorMsg" != $k && "" !== $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "KEY=" . $key;
        $sign = strtoupper(md5($signPars));
        return $sign == strtoupper($arr['sign']);
    }
}
