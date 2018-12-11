<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;

//34
class Tengkun implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            '1004' => '微信',
        ],
        2  => [ //支付宝扫码
            '992' => '支付宝',
        ],
        3  => [ //网银
            '962' => '中信银行',
            '963' => '中国银行',
            '964' => '农业银行',
            '965' => '建设银行',
            '967' => '工商银行',
            '968' => '浙商银行',
            '969' => '浙江稠州商业银行',
            '970' => '招商银行',
            '971' => '邮政储蓄',
            '972' => '兴业银行',
            '973' => '顺德农村信用合作社',
            '974' => '深发银行',
            '975' => '上海银行',
            '976' => '上海农商银行',
            '977' => '浦发银行',
            '978' => '平安银行',
            '979' => '南京银行',
            '980' => '民生银行',
            '981' => '交通银行',
            '982' => '华夏银行',
            '983' => '杭州银行',
            '984' => '广州市农村信用社|广州市商业银行',
            '985' => '广发银行',
            '986' => '光大银行',
            '987' => '东亚银行',
            '988' => '渤海银行',
            '989' => '北京银行',
            '990' => '北京农商银行',
        ],
        4  => [ //QQ扫码
            '1593' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            '1008' => '京东扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            '001007' => '银联扫码',
        ],
        8  => [ //微信WAP
            '1005' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            '01006' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            '1594' => 'QQWAP',
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
        ],
    ];

    //支付网关地址
    public $getwayUrl = 'http://api.tengkunkeji.cn/api.aspx';

    //请求参数
    public $pay_memberid = '1928'; //商户编号
    public $parterKey    = '0bb956300f2a4f3fbd4c1d5b6aae629e'; //商户md5key
    public $pay_notifyurl; //回调地址
    public $notifyUrl; //通知地址 没有

    public $pay_orderid; //订单号 是 是 上送订单号唯一, 字符长度 20
    public $pay_bankcode; //银行编码 是 是 参考后续说明
    public $pay_amount; //订单金额 是 是 商品金额
    public $pay_md5sign; //MD5 签名 是 否 请看 MD5 签名字段格式
    public $pay_version; //系统接口版本 是 否 固定值:vb1.0
    public $pay_applydate; //提交时间 是 否 时间格式(yyyyMMddHHmmss)：20161226181818
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
            $this->pay_memberid  = $conf['parterNo'];
            $this->parterKey     = $conf['parterKey'];
            $this->pay_notifyurl = $conf['callbackUrl'];
            $this->notifyUrl     = $conf['notifyUrl'];
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
        $this->pay_bankcode = intval($payType);
        $this->pay_amount   = $money;
        $this->pay_orderid  = $orderId;

        $this->params = [
            'pay_memberid'  => $this->pay_memberid,
            'pay_bankcode'  => $this->pay_bankcode,
            'pay_amount'    => $this->pay_amount,
            'pay_orderid'   => $this->pay_orderid,
            'pay_notifyurl' => $this->pay_notifyurl,

        ];
        //签名
        $stringSignTemp    = urldecode(http_build_query($this->params)) . $this->parterKey;
        $this->pay_md5sign = strtoupper(md5($stringSignTemp));
        //不签名参数
        $this->params['pay_md5sign']   = $this->pay_md5sign;
        $this->params['pay_version']   = 'vb1.0';
        $this->params['pay_applydate'] = date('YmdHis');

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
        $sign    = $params['sign'] ?? '';
        $orderid = $params['orderid'] ?? '';
        $opstate = $params['opstate'] ?? '';
        $ovalue  = $params['ovalue'] ?? '';

        $signStr = "orderid={$orderid}&opstate={$opstate}&ovalue={$ovalue}" . $this->parterKey;
        $mySign  = md5($signStr);

        if ($mySign == $sign) {
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
            return $url .'?' .http_build_query($data);
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
