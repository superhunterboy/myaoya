<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//40
class Zhongxinnew implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            'zxweixin' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'zxalp' => '支付宝扫码',
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
            'zxweixin' => '微信扫码',
        ],
        9  => [ //支付宝WAP
            'zxalp' => '支付宝扫码',
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

    //申明请求参数数组
    public $params = [];
    private static $instance;
    private $geturl="https://wz.lunamoon.net/app/pay/pay.html";

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
            $this->merchant_no  = $conf['parterNo'];
            $this->parterKey    = $conf['parterKey'];
            $this->backend_url  = $conf['callbackUrl'];
            $this->frontend_url = $conf['notifyUrl'];
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
        $this->bank_code    = $payType;
        $this->order_amount = $money;
        $this->order_no     = $orderId;

        if($payType == "zxweixin"){
            $mode = 1;
        }elseif($payType == "zxalp"){
            $mode = 2;
        }

        $this->params = [
            'appid'      => $this->merchant_no,
            'uid'  => md5("zx".rand(100000000,999999999)),
            'order'     => $this->order_no,
            'gid'   => 0,
            'price' => $this->order_amount,
            'mode'  => $mode,
            'notifyUrl' => $this->backend_url,
        ];
        //签名
        $str="appid=".$this->params['appid']."gid=".$this->params['gid']."mode=".$this->params['mode']."order=".$this->params['order']."price=".$this->params['price']."uid=".$this->params['uid']."secret=".$this->parterKey;
        $this->params['sn'] = md5($str);
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $url=$this->geturl."?".urldecode(http_build_query($this->params));

        $tmp = '<html>';
        $tmp .= '<head>';
        $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
        $tmp .= '<title>Pay Page</title>';
        $tmp .= '</head>';
        $tmp .= '<body style="display:none;">';
        $tmp .= '<form action="' . $url . '" method="post" name="orderForm">';
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
        $sign =$params['sn'] ?? '';
        $str="amount=".$params['amount']."gid=".$params['gid']."order=".$params['order']."transaction_id=".$params['transaction_id']."uid=".$params['uid']."secret=".$this->parterKey;

        return $sign == md5($str);
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {

    }


}
