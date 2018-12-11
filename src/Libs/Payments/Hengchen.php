<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//29
class Hengchen implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [ //微信扫码
            '1004' => '微信',
        ],
        2 => [ //支付宝扫码
            '01003' => '支付宝',
        ],
        3 => [ //网银
            '962' => '中信银行',
            '963' => '中国银行',
            '964' => '农业银行',
            '965' => '建设银行',
            '966' => '工商银行(仅限工行手机签约客户)',
            '967' => '工商银行(全国范围)',
            '968' => '浙商银行',
            '969' => '浙江稠州商业银行',
            '970' => '招商银行',
            '971' => '邮政储蓄',
            '972' => '兴业银行',
            '973' => '顺德农村信用合作社',
            '974' => '深圳发展银行',
            '975' => '上海银行',
            '976' => '上海农商银行',
            '977' => '浦东发展银行',
            '978' => '平安银行',
            '979' => '南京银行',
            '980' => '民生银行',
            '981' => '交通银行',
            '982' => '华夏银行',
            '983' => '杭州银行',
            '985' => '广发银行',
            '986' => '光大银行',
            '987' => '东亚银行',
            '988' => '渤海银行',
            '989' => '北京银行',
            '990' => '北京农商银行',
        ],
        4 => [ //QQ扫码
            '01009' =>'QQ钱包',
        ],
        5 => [ //京东扫码
        ],
        6 => [ //百度扫码
        ],
        7 => [ //银联扫码
        ],
        8 => [//微信WAP
            '01007' => '微信WAP',
        ],
        9 => [//支付宝WAP
            '01006' => '支付宝WAP',
        ],
        10 => [//QQWAP
            '01008' => 'QQWAP',
        ],
        11 => [//京东WAP
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    //支付网关地址
    public $getwayUrl = 'http://pay.dachen1.cn/bank/';

    //请求参数
    public $parter;         //商户编号
    public $parterKey;      //商户md5key
    public $callbackurl;    //回调地址
    public $hrefbackurl;    //通知地址 no't sign
    public $orderid;        //商户系统内部订单号
    public $value;          //金额
    public $payerIp;        //支付用户IP no't send
    public $attach;         //备注信息 no't send
    public $type;           //网银支付渠道
    public $sign;           //签名
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
            $this->parter      = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
            $this->callbackurl = $conf['callbackUrl'];
            $this->hrefbackurl = $conf['notifyUrl'];
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
        $this->type         = intval($payType);
        $this->value        = $money;
        $this->orderid      = $orderId;
        $this->payerIp      = Utils::getIp();

        $this->params = [
            'parter'        => $this->parter,         //商户编号
            'type'          => $this->type,           //网银支付渠道
            'value'         => $this->value,          //金额
            'orderid'       => $this->orderid,        //商户系统内部订单号
            'callbackurl'   => $this->callbackurl,    //回调地址
        ];
        //签名
        $md5Str = urldecode(http_build_query($this->params));
        $this->sign                     = md5($md5Str . $this->parterKey);
        $this->params['hrefbackurl']    = $this->hrefbackurl;    //通知地址
        $this->params['sign']           = $this->sign;

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
        $sign       = trim($params['sign'] ?? '');
        $orderid    = trim($params['orderid'] ?? '');
        $opstate    = trim($params['opstate'] ?? '');
        $ovalue     = trim($params['ovalue'] ?? '');

        $md5Str     = "orderid={$orderid}&opstate={$opstate}&ovalue={$ovalue}".$this->parterKey;
        $mySign     = md5($md5Str);

        if ($mySign === $sign && !empty($orderid)) {
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