<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Az implements WMPay
{
    /**
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
            'wxcode'=>'微信扫码'
        ],
        2  => [ //支付宝扫码
            'alipay'=>'支付宝扫码'
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
            'unionpay'=>'银联扫码'
        ],
        8  => [ //微信WAP
            'wxwap'=>'微信WAP'
        ],
        9  => [ //支付宝WAP
            'alipaywap'=>'支付宝WAP'
        ],
        10 => [ //QQWAP
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
            'unionpay'=>'银联WAP'
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'http://www.yaozhengsm.com/GateWay/ReceiveBank.aspx';

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
            $this->parterNo  = $conf['parterNo'];
            $this->parterKey = $conf['parterKey'];
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

        $this->params['p0_Cmd']             ="Buy";
        $this->params['p1_MerId']           =$this->parterNo;
        $this->params['p2_Order']           =$this->orderNo;
        $this->params['p3_Amt']             =$this->money;
        $this->params['p4_Cur']             ="CNY";
        $this->params['p5_Pid']             ='abc'.time().rand(10000,99999);
        $this->params['p6_Pcat']            ='def'.time().rand(10000,99999);
        $this->params['p7_Pdesc']           ='ghi'.time().rand(10000,99999);
        $this->params['p8_Url']             =$this->callbackUrl;
        $this->params['p9_SAF']             ="0";
        $this->params['pd_FrpId']           =$payType;
        $this->params['pr_NeedResponse']    ="1";
        $this->params['hmac']               =$this->getSignStr($this->params,$this->parterKey);

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
        foreach ($this->params as $key => $value) {
            $tmp .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        $tmp .= '</form>';
        $tmp .= '<script type="text/javascript">';
        $tmp .= 'document.orderForm.submit();';
        $tmp .= '</script>';
        $tmp .= '</body>';
        $tmp .= '</html>';

        return $tmp;

    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);

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
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg .= $val;
        }
        $key = iconv("GB2312","UTF-8",$paysecret);
        $data = iconv("GB2312","UTF-8",$msg);

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*",md5($k_ipad . $data)));
    }
}
