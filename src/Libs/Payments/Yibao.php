<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Yibao implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
        ],
        3  => [ //网银
            'ICBC-NET-B2C'       => '工商银行',
            'CMBCHINA-NET-B2C'   => '招商银行',
            'CCB-NET-B2C'        => '建设银行',
            'BOCO-NET-B2C'       => '交通银行[借]',
            'CIB-NET-B2C'        => '兴业银行',
            'CMBC-NET-B2C'       => '中国民生银行',
            'CEB-NET-B2C'        => '光大银行',
            'BOC-NET-B2C'        => '中国银行',
            'PINGANBANK-NET-B2C' => '平安银行',
            'ECITIC-NET-B2C'     => '中信银行',
            'SDB-NET-B2C'        => '深圳发展银行',
            'GDB-NET-B2C'        => '广发银行',
            'SHB-NET-B2C'        => '上海银行',
            'SPDB-NET-B2C'       => '上海浦东发展银行',
            'HXB-NET-B2C'        => '华夏银行「借」',
            'BCCB-NET-B2C'       => '北京银行',
            'ABC-NET-B2C'        => '中国农业银行',
            'POST-NET-B2C'       => '中国邮政储蓄银行「借」',
            'BJRCB-NET-B2C'      => '北京农村商业银行「借」',
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

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://www.yeepay.com/app-merchant-proxy/node';

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
        $this->money   = $money;

        $this->params = [
            'p0_Cmd'             => 'Buy',
            'p1_MerId'           => $this->parterNo,
            'p2_Order'           => $this->orderNo,
            'p3_Amt'             => $this->money, // 元
            'p4_Cur'             => 'CNY',
            'p5_Pid'             => '',
            'p6_Pcat'            => '',
            'p7_Pdesc'           => '',
            'p8_Url'             => $this->callbackUrl,
            'p9_SAF'             => '',
            'pa_MP'              => '',
            'pb_ServerNotifyUrl' => $this->notifyUrl,
            'pd_FrpId'           => $payType,
            'pm_Period'          => 7,
            'pn_Unit'            => 'day',
            'pr_NeedResponse'    => 1,
            'pt_UserName'        => '',
            'pt_PostalCode'      => '',
            'pt_Address'         => '',
            'pt_TeleNo'          => '',
            'pt_Mobile'          => '',
            'pt_Email'           => '',
            'pt_LeaveMessage'    => '',
            'hmac_safe'          => '',
            'hmac'               => '',
        ];

        $this->params['hmac_safe'] = Utils::HmacMd5($this->getSignStr($this->params), $this->parterKey);
        $this->params['hmac']      = Utils::HmacMd5(implode($this->params), $this->parterKey);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        print_r($this->params);

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
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        ksort($params);
        $temp = '';
        foreach ($params as $key => $value) {
            if ($key != "hmac" && $key != "hmac_safe" && $value != '') {
                $temp .= $value . '#';
            }
        }
        return rtrim(trim($temp), '#');
    }
}
