<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Yinxin implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'wxpay' => 'PC微信扫码',
        ],
        2  => [ //支付宝扫码
            'alipay' => '支付宝扫码',
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'qqpay' => 'QQ扫码',
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

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://www.rca7ti.cn/api/submit.php'; #   请求网关地址
    public $parter; #   商户ID
    public $callbackUrl; #   下行异步通知地址
    public $notifyUrl; #   下行同步通知地址(付款完毕后返回,可空)
    public $key; #   商户秘钥

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
            $this->parter      = $conf['parterNo'];
            $this->key         = $conf['parterKey'];
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
        $this->money   = $money; // 分
        $this->params  = [
            'pid'          => $this->parter,
            'type'         => $payType,
            'out_trade_no' => $orderId,
            'notify_url'   => $this->callbackUrl,
            'return_url'   => $this->notifyUrl,
            'name'         => "virtual",
            'money'        => number_format($money, 2, '.', ''),
            'sitename'     => "supermarket",
            'typepay'      => 'ICBC',
        ];

        ksort($this->params);

        $this->params['sign'] = md5(urldecode(http_build_query($this->params)) . $this->key);

        $this->params['sign_type'] = "MD5";

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
        $tmp .= "<form action='" . $this->getwayUrl . "?_input_charset=utf-8' method='POST' name='orderForm'>";
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
        $sign = $params['sign'];

        unset($params['sign_type'], $params['sign']);
        $mySign = md5(urldecode(http_build_query($params)) . $this->key);

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
    private function getSignStr($parter)
    {
    }

}
