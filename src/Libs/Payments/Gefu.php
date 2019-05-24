<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Gefu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'wx' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'ali' => '支付宝扫码',
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'qq' => 'QQ',
        ],
        5  => [ //京东扫码
            'jd' => '京东',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
        ],
        8  => [ //微信WAP
            'wxwap' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            'aliwap' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            'qqwap' => 'QQWAP',
        ],
        11 => [ //京东WAP
            'jdwap' => '京东WAP',
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
    public $getwayUrl = 'https://wwww.chuangyanqiche.com/api.php/pay/index'; # 支付网关地址

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
            $this->parter      = $conf['parterNo'];
            $this->key         = $conf['parterKey'];
            $this->notifyUrl   = $conf['callbackUrl'];
            $this->callbackUrl = $conf['notifyUrl'];
        }
    }

    private function __clone()
    {
    }

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
        $this->amount = $money;
        $this->params = [
            'parter'      => $this->parter,
            'value'       => $money,
            'type'        => $payType,
            'orderid'     => $orderId,
            'notifyurl'   => $this->notifyUrl,
            'callbackurl' => $this->callbackUrl,
        ];

        ksort($this->params);

        $this->params['sign'] = $this->getSignStr($this->params);

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
        $tmp .= "<form action='" . $this->getwayUrl . "' method='GET' name='orderForm'>";
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
        $sign   = $params['sign'];
        $str = 'opstate='.$params['opstate'].'&orderid='.$params['orderid'].'&ovalue='.$params['ovalue']."&parter=".$params['parter']."&key=".$this->key;
        $mySign=md5($str);
        return $sign == $mySign;
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
    private function getSignStr($param)
    {
        $str = 'callbackurl='.$param['callbackurl'].'&notifyurl='.$param['notifyurl'].'&orderid='.$param['orderid'].'&parter='.$param['parter'].'&type='.$param['type'].'&value='.$param['value'].'&key='.$this->key;

        $signStr = md5($str);

        return $signStr;

    }

}
