<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

//37
class Yunzhifu implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            '2' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            '1' => '支付宝扫码',
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
    ];

    //支付网关地址
    public $getwayUrl = 'https://pay.adhdu.cn';

    //请求参数
    public $uid   = "3cdc9dfbd6893b8b620d8fb2"; //"此处填写网站设置中的uid";
    public $token = "30d12dd2bb5ad62e63de528788368953"; //"此处填写网站设置中的Token";
    public $notify_url; //回调地址
    public $return_url; //通知地址

    public $price; //金额
    public $istype; //支付类型
    public $orderuid  = "none"; //此处传入您网站用户的用户名，方便在我们网站后台查看是谁付的款，强烈建议加上。可忽略。
    public $goodsname = "good_Game";
    public $orderid; //每次有任何参数变化，订单号就变一个吧。
    public $key; //签名

    //申明请求参数数组
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
            $this->uid        = $conf['parterNo'];
            $this->token      = $conf['parterKey'];
            $this->notify_url = $conf['callbackUrl'];
            $this->return_url = $conf['notifyUrl'];
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
        $this->istype  = $payType;
        $this->price   = $money;
        $this->orderid = $orderId;

        $this->params = [
            'goodsname'  => $this->goodsname,
            'istype'     => $this->istype,
            'notify_url' => $this->notify_url,
            'orderid'    => $this->orderid,
            'orderuid'   => $this->orderuid,
            'price'      => $this->price,
            'return_url' => $this->return_url,
            'uid'        => $this->uid,
        ];
        //签名
        $this->key           = md5($this->goodsname . $this->istype . $this->notify_url . $this->orderid . $this->orderuid . $this->price . $this->return_url . $this->token . $this->uid);
        $this->params['key'] = $this->key;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        return $this->htmlSend($this->getwayUrl, $this->params);
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $p_id      = $params["p_id"] ?? '';
        $orderid   = $params["orderid"] ?? '';
        $price     = $params["price"] ?? '';
        $realprice = $params["realprice"] ?? '';
        $orderuid  = $params["orderuid"] ?? '';
        $sign      = $params["key"] ?? '';

        $mySign = md5($orderid . $orderuid . $p_id . $price . $realprice . $this->token);

        if ($mySign === $sign) {
            return true;
        }
        return false;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {

    }

    /*
     * html 表单提交
     */
    private function htmlSend($url, $data, $method = 'post')
    {
        if ($method == 'get') {
            return $url . '?' . http_build_query($data);
        } elseif ($method == 'post') {
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $url . '" method="post" name="orderForm">';
            foreach ($data as $key => $value) {
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

    }

}
