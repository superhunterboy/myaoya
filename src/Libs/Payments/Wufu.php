<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Wufu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'WEIXIN_NATIVE' => 'PC微信扫码',
        ],
        2  => [ //支付宝扫码
            'ALIPAY_NATIVE' => '支付宝扫码',
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'QQ_NATIVE' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            'JD_NATIVE' => '京东扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            'UNIONPAY_NATIVE' => '银联扫码',
        ],
        8  => [ //微信WAP
            'WEIXIN_H5' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            'ALIPAY_H5' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            'QQ_H5' => 'QQWAP',
        ],
        11 => [ //京东WAP
            'JD_H5' => '京东WAP',
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
            // 'UNIONPAY_H5' => '银联WAP',
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://pay.8331vip.com/uniThirdPay'; #   请求网关地址
    public $svcName; # 服务名称
    public $merId; # 商户ID
    public $merchOrderId; # 商户订单号
    public $tranType; # 交易类型
    public $pName; # 商品名称
    public $amt; # 金额
    public $notifyUrl; # 下行异步通知地址
    public $retUrl; # 下行同步通知地址(付款完毕后返回,可空)
    public $key; # 商户秘钥
    public $md5value; # 签名信息
    public $strRetStr;

    /**
     * 提交参数
     * @var array
     */
    public $params = [];
    public $orderNo;

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
            $this->merId     = $conf['parterNo'];
            $this->key       = $conf['parterKey'];
            $this->notifyUrl = $conf['callbackUrl'];
            $this->retUrl    = $conf['notifyUrl'];
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
        if ($payType == 'WEIXIN_H5' || $payType == 'WEIXIN_NATIVE' || $payType == 'ALIPAY_H5' || $payType == 'ALIPAY_NATIVE' || $payType == 'QQ_H5' || $payType == 'QQ_NATIVE' || $payType == 'JD_H5' || $payType == 'JD_NATIVE' || $payType = 'UNIONPAY_NATIVE') {
            $this->svcName = 'UniThirdPay';
        }
        $this->orderNo = $orderId;
        $this->amt     = $money;
        $this->params  = [
            'amt'          => $money * 100,
            'merData'      => 'SUCCESS',
            'merId'        => $this->merId,
            'merchOrderId' => $orderId,
            'notifyUrl'    => $this->notifyUrl,
            'pName'        => '手机壳',
            'retUrl'       => $this->retUrl,
            'showCashier'  => '1',
            'svcName'      => $this->svcName,
            'tranType'     => $payType,

        ];

        $this->params['md5value'] = $this->getSignStr($this->params, $this->key);

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
        $tmp .= '<body>';
        $tmp .= "<form action='" . $this->getwayUrl . "' method='POST' name='orderForm'>";
        foreach ($this->params as $key => $value) {
            $tmp .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\n";
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
        $sign   = $params['md5value'];
        $mySign = $this->getSignStr($params, $this->key);
        file_put_contents('C:\wwwroot\pay\src\Controllers\1.txt', $params['md5value']."---------------".$mySign);
        $arr = array("sign" => $sign , "mySign" => $mySign);

        if ($sign === $mySign) {
            return ture;
        }
        return false;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    /**
     * getSignStr 签名生成
     */
    private function getSignStr($param, $md5Key)
    {
        $strRet = '';
        ksort($param);
        foreach ($param as $key => $value) {
            if ($value === "") {
                continue;
            }
            if ($key == "md5value") {
                continue;
            }
            $strRet = $strRet . $value;
        }
        $strRet = $strRet . $md5Key;
        return strtoupper(md5($strRet));
    }

}
