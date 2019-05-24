<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Daniu implements WMPay
{
    /**====
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
        ],
        5  => [ //京东扫码
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
        ],
        8  => [ //微信WAP
        ],
        9  => [ //支付宝WAP
        ],
        10 => [ //QQWAP
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
        ],
        14 => [ //云闪付
            "daniuyun"=>"云闪付"
        ],
        15 => [ //云闪付WAP
            "daniuyun"=>"云闪付WAP"
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://www.pay770.com/Pay_Index.html';

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;

    /**
     * 商户key
     * @var string
     */
    public $parterKey;
    public $pubKey;
    public $priKey;
    public $payke;
    public $paysecret;

    /**
     * 回调地址
     * @var string
     */
    public $callbackUrl;

    /**
     * 通知地址
     * @var string
     */
    public $notifyUrl;

    /**
     * 签名
     * @var string
     */
    public $sign;

    /**
     * 提交参数
     * @var array
     */
    public $params = [];
    public $orderNo;
    public $money;

    /**
     * 支付对象
     * @var object
     */
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
            $this->parterNo = $conf['parterNo'];

            $this->parterKey = $conf['parterKey']; // {"pubKey":"...","priKey":"..."}

            $this->callbackUrl = $conf['callbackUrl'];
            $this->notifyUrl   = $conf['notifyUrl'];
        }
    }

    private function __clone()
    {}

    /**
     * 获取支付类型
     */
    public function getPayType()
    {
        return $this->paymentType;
    }

    /**
     * 签名
     */
    public function signature($payType, $money, $orderId)
    {
        $this->orderNo = $orderId;
        $this->money   = $money;

        $params = array(
            "pay_memberid" => $this->parterNo ,
            "pay_orderid" => $this->orderNo,
            "pay_amount" => $this->money,
            "pay_applydate" => date("Y-m-d H:i:s"),
            "pay_bankcode" => "909",
            "pay_notifyurl" => $this->callbackUrl,
            "pay_callbackurl" => $this->callbackUrl,
        );

        ksort($params);
        $md5str = "";
        foreach ($params as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        //echo($md5str . "key=" . $Md5key);
        $sign = strtoupper(md5($md5str . "key=" . $this->parterKey));
        $params["pay_md5sign"] = $sign;
        $this->params=$params;
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
             $tmp = '<html>';
             $tmp .= '<head>';
             $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
             $tmp .= '<title>Pay Page</title>';
             $tmp .= '</head>';
             $tmp .= '<body style="display:none;">';
             $tmp .= '<form action="' . $this->getwayUrl . '" method="post" name="orderForm">';
                             foreach ($this->params as $key => $val) {
                                $tmp .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
                             }
             $tmp .= '</form>';
             $tmp .= '<script type="text/javascript">';
             $tmp .= 'document.orderForm.submit();';
             $tmp .= '</script>';
             $tmp .= '</body>';
             $tmp .= '</html>';
             echo $tmp;
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];

        unset($params['sign']);
        unset($params['attach']);

        $returnsign=$this->getSignStr($params,$this->parterKey);
        return $sign == $returnsign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

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
