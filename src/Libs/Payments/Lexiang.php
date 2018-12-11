<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Lexiang implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            2 => '微信',
        ],
        2  => [ //支付宝扫码
            1 => '支付宝',
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
            'wxwap' => '微信',
        ],
        9  => [ //支付宝WAP
            'aliwap' => '支付宝',
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
    public $getwayUrl = 'https://www.moe168.com/pay';

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;
    public $parterKey;

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
            $this->parterNo    = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
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
        $this->money   = sprintf("%.2f", $money);

        if ($payType == 'wxwap') {
            $payType = 2;
        } elseif ($payType == 'aliwap') {
            $payType = 1;
        }

        $this->params = [
            'uid'        => $this->parterNo,
            'price'      => $this->money, // 元
            'istype'     => $payType,
            'notify_url' => $this->callbackUrl,
            'return_url' => $this->notifyUrl,
            'orderid'    => $this->orderNo,
            'orderuid'   => '',
            'goodsname'  => 'CZ',
            'attach'     => '',
            'key'        => '',
        ];

        $temp = $this->getSignStr($this->params);

        $this->params['key'] = strtolower(md5($temp));

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
        $orderid           = $params['orderid'];
        $orderuid          = $params['orderuid'];
        $platform_trade_no = $params['platform_trade_no'];
        $price             = $params['price'];
        $realprice         = $params['realprice'];
        return $params['key'] == strtolower(md5(sprintf("lx1%slx2%slx3%slx4%slx5%s%s", $orderid, $orderuid, $platform_trade_no, $price, $realprice, $this->parterKey)));
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        $goodsname  = $params['goodsname'];
        $istype     = $params['istype'];
        $notify_url = $params['notify_url'];
        $orderid    = $params['orderid'];
        $orderuid   = $params['orderuid'];
        $price      = $params['price'];
        $return_url = $params['return_url'];
        $uid        = $params['uid'];
        return sprintf("lx1%slx2%slx3%slx4%slx5%slx6%slx7%s%s%s", $goodsname, $istype, $notify_url, $orderid, $orderuid, $price, $return_url, $this->parterKey, $uid);
    }
}
