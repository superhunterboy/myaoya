<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//39
class Xifu implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            'WXPAY' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'ALIPAY' => '支付宝扫码',
        ],
        3  => [ //网银
            'CMB'     => '招商银行',
            'ICBC'    => '工商银行',
            'CCB'     => '建设银行',
            'BOC'     => '中国银行',
            'ABC'     => '农业银行',
            'BOCM'    => '交通银行',
            'SPDB'    => '浦发银行',
            'CGB'     => '广发银行',
            'CITIC'   => '中信银行',
            'CEB'     => '光大银行',
            'CIB'     => '兴业银行',
            'PAYH'    => '平安银行',
            'CMBC'    => '民生银行',
            'HXB'     => '华夏银行',
            'PSBC'    => '邮储银行',
            'BCCB'    => '北京银行',
            'oSHBANK' => '上海银行',
        ],
        4  => [ //QQ扫码
            'QQPAY' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            'JDPAY' => '京东扫码',
        ],
        6  => [ //百度扫码
            'BDPAY' => '百度扫码',
        ],
        7  => [ //银联扫码
            'UNIONQRPAY' => '银联扫码',
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

    //支付网关域名
    public $getwayHost = 'https://ebank.xifpay.com/';

    //请求参数
    public $merchantId = '100000000002565';
    public $parterKey  = 'ad6971e0833068af0ceg52bc4a7efe2cg6051gd8a4b753b9a9ff35c2bb5df3b4';
    public $notifyUrl; //回调地址
    public $returnUrl; //通知地址

    public $body    = 'ready'; //商品的具体描述
    public $charset = 'UTF-8'; //参数编码字符集
    public $defaultbank; //网银代码，当支付方式为bankPay时，该值为空；支付方式为directPay时该值必传，值见银行列表
    public $isApp = 'web'; //接入方式，当该值传“app”时,返回二维码地址;值为“web”时,直接在收银台页面上显示二维码；值为“H5”时,会在手机端唤醒支付app
    public $orderNo; //商户订单号，务必确保在系统中唯一
    public $paymenttype = '1'; //支付类型，固定值为1
    public $paymethod   = 'directPay'; //支付方式，directPay：直连模式；bankPay：收银台模式
    public $service     = 'online_pay'; //固定值online_pay，表示网上支付
    public $title       = 'sunny'; //商品的名称，请勿包含字符
    public $totalFee; //订单金额，单位为RMB元
    public $signType = 'SHA'; //签名方式 ：SHA
    public $sign; //加签结果

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
            $this->merchantId = $conf['parterNo'];
            $this->parterKey  = $conf['parterKey'];
            $this->notifyUrl  = $conf['callbackUrl'];
            $this->returnUrl  = $conf['notifyUrl'];
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
        $this->defaultbank = $payType;
        $this->totalFee    = $money;
        $this->orderNo     = $orderId;

        $this->params = [
            'merchantId'  => $this->merchantId,
            'body'        => $this->body,
            'charset'     => $this->charset,
            'defaultbank' => $this->defaultbank,
            'isApp'       => $this->isApp,
            'notifyUrl'   => $this->notifyUrl,
            'orderNo'     => $this->orderNo,
            'paymentType' => $this->paymenttype,
            'paymethod'   => $this->paymethod,
            'returnUrl'   => $this->returnUrl,
            'service'     => $this->service,
            'title'       => $this->title,
            'totalFee'    => $this->totalFee,
            'signType'    => $this->signType,
        ];
        //签名
        $this->params['sign'] = strtoupper(sha1(Utils::getXifuSignStr($this->params) . $this->parterKey));

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $getwayUrl = $this->getwayHost . 'payment/v1/order/'. $this->merchantId .'-'. $this->orderNo;
        return $this->htmlSend($getwayUrl, $this->params);
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $signPars = "";
        ksort($params);
        foreach ($params as $k => $v) {
            if ("" != $v && "sign" != $k && "signType" != $k && "respCode" != $k && "respMessage" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return $sign == strtoupper(sha1(rtrim($signPars, '&') . $this->parterKey));
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
