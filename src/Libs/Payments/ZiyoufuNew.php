<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//平台21
class ZiyoufuNew implements WMPay
{
    public $payType = [
        1 => [
            '10000168' => '微信扫码',
        ],
        2 => [
            '10000169' => '支付宝扫码',
        ],
        3 => [
        ],
        4 => [
            '10000170' => 'QQ钱包',
        ],
        5 => [
        ],
        6 => [
        ],
        7 => [
        ],
        8 => [//微信WAP
        ],
        9 => [//支付宝WAP
        ],
        10 => [//QQWAP
        ],
        11 => [//京东WAP
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    public $getwayUrl = 'http://m.zypay.net/api/pay/GetQRCode';

    public $merchantNo; //商户号
    public $parterKey; //商户key
    public $outTradeNo; //订单号
    public $notifyUrl; //回掉地址
    public $body; //商品名称
    public $attach; //商品介绍
    public $payWay; //支付方式
    public $amount; //金额
    public $sign = '';

    public $params = [];

    private static $instance;

    public static function getInstance($conf = [])
    {

        if (!(static::$instance instanceof static )) {

            static::$instance = new static($conf);

        }

        return static::$instance;

    }

    private function __construct($conf)
    {
        if ($conf) {
            $this->merchantNo = $conf['parterNo'];
            $this->parterKey = $conf['parterKey'];
            $this->notifyUrl = $conf['callbackUrl'];
            //$this->notifyUrl = $conf['notifyUrl'];  没有同步通知
        }

    }

    private function __clone()
    {}

    public function getPayType()
    {
        return $this->payType;
    }

    public function signature($type, $money, $orderId)
    {
        $this->attach       = $orderId;
        $this->outTradeNo   = $orderId;
        $this->body         = $orderId;
        $this->amount       = $money * 100;
        $this->payWay       = $type;

        $this->params = [
            'MerchantNo'    => $this->merchantNo, //商户号
            'OutTradeNo'    => $this->outTradeNo, //订单号
            'Body'          => $this->body, //商品名称
            'PayWay'        => $this->payWay, //支付方式
            'Amount'        => $this->amount, //金额
        ];
        //不签名字段
        ksort($this->params);
        //$signPars = urldecode(http_build_query($this->params)) . "&key=" . $this->parterKey;
        $signStr = '';
        foreach ($this->params as $key => $value) {
            $signStr .= $value;
        }
        $signStr .= $this->parterKey;
        $this->sign = md5(strtolower($signStr));
        $this->params['Attach'] = $this->attach; //商品介绍
        $this->params['NotifyUrl'] = $this->notifyUrl; //回掉地址
        $this->params['Sign']   = $this->sign;

        return $this;
    }

    public function payment()
    {
        $client = new Client();

        $res = $client->request('POST', $this->getwayUrl, [
            'verify'=>false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
            ],
            'form_params' => $this->params
        ]);                                                                                                                                                                                 //return $res->getBody() . '**************' . json_encode($this->params);

         if ($res->getStatusCode() == '200') {
            $returnJson = $res->getBody();
            if ($data = json_decode($returnJson, true)) {
                $code = $data['Code'] ?? '';
                $msg  = $data['Msg'] ?? '';
                if ($code == '1000') {
                    $qrcode = $data['Data']['CodeUrl'] ?? '';
                    return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->outTradeNo . '&fee=' . sprintf("%.2f",$this->amount/100) . '&qrcode=' . urlencode($qrcode);
                } else {
                    return json_encode(['order'=>$this->outTradeNo, 'msg'=>$msg]);
                }
            }
        }
        return false;
    }

    public function verifySign($params = [])
    {
        //OrderNo + MerchantNo + Amount + OutTradeNo + RetCode + (商户秘钥)).ToLower ()
        $sign = $params['Sign'];
        unset($params['Sign']);
        unset($params['Attach']);
        unset($params['TradeTime']);
        unset($params['RetMsg']);
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            $signStr .= $value;
        }
        $signStr .= $this->parterKey;

        //$signPars = urldecode(http_build_query($params)) . "&key=" . $this->parterKey;
        $mySign = md5(strtolower($signStr));

        if ($sign == $mySign) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {

    }
}