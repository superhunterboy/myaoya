<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Yf implements WMPay
{
    /**  盈付
     *   支付类型
     */

    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            "903" => "支付宝扫码",
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
            "904" => "支付宝WAP",
        ],
        10 => [ //QQWAP
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'http://www.yfpay.me/Pay_Index.html';

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

        $pay_applydate = date("Y-m-d H:i:s");

        $this->params["pay_memberid"]    = $this->parterNo;
        $this->params["pay_orderid"]     = $this->orderNo;
        $this->params["pay_amount"]      = $this->money;
        $this->params["pay_applydate"]   = $pay_applydate;
        $this->params["pay_bankcode"]    = $payType;
        $this->params["pay_notifyurl"]   = $this->callbackUrl;
        $this->params["pay_callbackurl"] = $this->callbackUrl;
        ksort($this->params);
        foreach ($this->params as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $this->params["pay_md5sign"]     = strtoupper(md5($md5str . "key=" . $this->parterKey));
        $this->params['pay_attach']      = "1234|456";
        $this->params['pay_productname'] = 'VIP基础服务' . time();
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
        // {
        //     "memberid":"10105",
        //     "orderid":"201904250754168078238",
        //     "transaction_id":"yf20190425155446549756",
        //     "amount":"20.0000",
        //     "datetime":"20190425155737",
        //     "returncode":"00",
        //     "sign":"17654D58BEC9AFF4BC05A699D06130AA",
        //     "attach":"1234|456"
        // }
        $msg  = '';
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['attach']);
        ksort($params);
        // reset($params);
        foreach ($params as $key => $value) {
            $msg .= $key . "=" . $value . "&";
        }
        // $msg = substr($msg, 0, (strlen($msg) - 1));

        $mySign = strtoupper(md5($msg .'key='. $this->parterKey));
        
        return $sign == $mySign;

    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [], $paysecret = '')
    {
        $msg = "parter=" . $array['parter'] . "&type=" . $array['type'] . "&orderid=" . $array['orderid'] . "&callbackurl=" . $this->callbackUrl . $paysecret;
        return md5($msg);
    }
}
