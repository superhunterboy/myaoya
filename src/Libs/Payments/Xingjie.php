<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

//47
class Xingjie implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            'weixin' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'alipay' => '支付宝扫码',
        ],
        3  => [ //网银
            'ICBC'  => '工商银行',
            'ABC'   => '农业银行',
            'BOCSH' => '中国银行',
            'CCB'   => '建设银行',
            'CMB'   => '招商银行',
            'SPDB'  => '浦发银行',
            'GDB'   => '广发银行',
            'PSBC'  => '邮储银行',
            'CNCB'  => '中信银行',
            'CMBC'  => '民生银行',
            'CEB'   => '光大银行',
            'HXB'   => '华夏银行',
            'CIB'   => '兴业银行',
            'PAB'   => '平安银行',
        ],
        4  => [ //QQ扫码
            'qqQR' => 'QQ扫码',
        ],
        5  => [ //京东扫码
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            'ylsm' => '银联扫码',
        ],
        8  => [ //微信WAP
            'wxh5' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            'alipaywap' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            'qqwap' => 'QQWAP',
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
        ],
    ];

    //支付网关地址
    public $getwayUrl = 'http://www.xingjiepay.com/apisubmit';

    //请求参数
    public $customerid; //商户编号
    public $parterKey; //商户md5key
    public $notifyurl; //回调地址
    public $returnurl; //通知地址

    public $version = '1.0'; //版本号
    public $sdorderno; //商户订单号
    public $total_fee; //订单金额
    public $paytype; //支付编号
    public $bankcode; //银行编号
    public $sign; //签名

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
            $this->customerid = $conf['parterNo'];
            $this->parterKey  = $conf['parterKey'];
            $this->notifyurl  = $conf['callbackUrl'];
            $this->returnurl  = $conf['notifyUrl'];
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
        $this->total_fee = sprintf("%.2f", $money);
        $this->sdorderno = $orderId;
        if (in_array($payType, ['weixin', 'alipay', 'qqQR', 'ylsm', 'wxh5', 'alipaywap', 'qqwap'])) {
            $this->paytype  = $payType;
            $this->bankcode = 'ICBC';
        } else {
            $this->paytype  = 'bank';
            $this->bankcode = $payType;
        }

        $this->params = [
            'version'    => $this->version,
            'customerid' => $this->customerid,
            'sdorderno'  => $this->sdorderno,
            'total_fee'  => $this->total_fee,
            'paytype'    => $this->paytype,
            'bankcode'   => $this->bankcode,
            'notifyurl'  => $this->notifyurl,
            'returnurl'  => $this->returnurl,
        ];
        //签名
        $md5Str               = 'version=' . $this->version . '&customerid=' . $this->customerid . '&total_fee=' . $this->total_fee . '&sdorderno=' . $this->sdorderno . '&notifyurl=' . $this->notifyurl . '&returnurl=' . $this->returnurl;
        $this->sign           = md5($md5Str . '&' . $this->parterKey);
        $this->params['sign'] = $this->sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        return $this->htmlSend($this->getwayUrl, $this->params);
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign       = $params['sign'] ?? '';
        $customerid = $params['customerid'] ?? '';
        $status     = $params['status'] ?? '';
        $sdpayno    = $params['sdpayno'] ?? '';
        $sdorderno  = $params['sdorderno'] ?? '';
        $total_fee  = $params['total_fee'] ?? '';
        $paytype    = $params['paytype'] ?? '';

        $mySign = md5('customerid=' . $customerid . '&status=' . $status . '&sdpayno=' . $sdpayno . '&sdorderno=' . $sdorderno . '&total_fee=' . $total_fee . '&paytype=' . $paytype . '&' . $this->parterKey);

        if ($mySign === $sign) {
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
