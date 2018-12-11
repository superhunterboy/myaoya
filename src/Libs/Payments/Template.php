<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Template implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = '';

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
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

}
