<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//26
class Qianying implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '102'  => '微信',
        ],
        2 => [
            '101'   => '支付宝',
        ],
        3 => [
        ],
        4 => [
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

    //Windows支付网关
    public $getwayUrl = 'http://110.92.66.140/send.php';

    public $mer_id;         //商户号
    public $totalAmount;    //支付方式
    public $money;          //金额 只支持整数
    public $order_no;       //订单号
    public $callback;       //回掉地址
    public $pay_add = 'https://7001.com/';        //支付前台地址
    public $notify;         //通知地址
    public $pri_key;        //商户密钥
    public $pay_gateway = 'http://www.qianyingnet.com/pay/';    //千应支付网关

    //提交参数
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
            $this->mer_id      = $conf['parterNo'];
            $this->pri_key     = $conf['parterKey'];
            $this->callback    = $conf['callbackUrl'];
            $this->notify      = $conf['notifyUrl'];
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
        $this->money       = (int)$money;
        $this->order_no    = $orderId;
        $this->totalAmount = $payType;

        $this->params     = [
            'mer_id'        => $this->mer_id,         //商户号
            'totalAmount'   => $this->totalAmount,    //支付方式
            'money'         => $this->money,          //金额 只支持整数
            'order_no'      => $this->order_no,       //订单号
            'callback'      => $this->callback,       //回掉地址
            'pay_add'       => $this->pay_add,        //支付前台地址
            'notify'        => $this->notify,         //通知地址
            'pri_key'       => $this->pri_key,        //商户密钥
            'pay_gateway'   => $this->pay_gateway,    //千应支付网关
        ];

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
        $sign    = $params['sign'] ?? '';
        $orderid = $params['oid'] ?? '';
        $status  = $params['status'] ?? '';
        $money   = $params['m'] ?? '';

        $signStr = 'oid='.$orderid.'&status='.$status.'&m='.$money . $this->pri_key;
        $mySign  = strtoupper(md5($signStr));

        if ($sign == $mySign) {
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

}