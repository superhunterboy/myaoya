<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//30
class Chengwo implements WMPay
{
    // 支付类型
    public $paymentType = [
        1 => [ //微信扫码
            'WXZF' => '微信',
        ],
        2 => [ //支付宝扫码
            'ALIPAY' => '支付宝',
        ],
        3 => [ //网银
            'SDB' => '光大银行',
            'CIB' => '中国银行',
            'BCM' => '平安银行',
            'CEB' => '广发银行',
            'BOC' => '宁波银行',
        ],
        4 => [ //QQ扫码
            'QQZF' =>'QQ钱包',
        ],
        5 => [ //京东扫码
        ],
        6 => [ //百度扫码
        ],
        7 => [ //银联扫码
        ],
        8 => [//微信WAP
            'WXZFWAP' => '微信WAP',
        ],
        9 => [//支付宝WAP
            'ALIPAYWAP' => '支付宝WAP',
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

    //支付网关地址
    public $getwayUrl = 'http://pay.cwopay.com/'; //http://www.cwopay.com/Pay_Index.html

    //请求参数
    public $pay_memberid;       //商户编号
    public $parterKey;          //商户md5key
    public $pay_notifyurl;      //回调地址
    public $pay_callbackurl;    //通知地址 no't sign
    public $pay_orderid;        //订单号
    public $pay_amount;         //金额
    public $pay_applydate;      //订单提交时间 如:2014-12-26 18:18:18
    public $pay_bankcode;       //银行编号
    public $tongdao;            //调用通道编码 如：WeiXinWap
    public $pay_md5sign;        //MD5签名字段
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
            $this->pay_memberid     = $conf['parterNo'];
            $this->parterKey        = $conf['parterKey'];
            $this->pay_notifyurl    = $conf['callbackUrl'];
            $this->pay_callbackurl  = $conf['notifyUrl'];
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
        //$payType_               = $payType['payType'] ?? '';
        //$device                 = $payType['device'] ?? '';
        $this->pay_bankcode     = $payType;
        $this->pay_amount       = $money;
        $this->pay_orderid      = $orderId;
        $this->pay_applydate    = date('Y-m-d H:i:s');
        if ($payType == 'WXZF') {
            $this->tongdao = 'WxSm';
        } elseif ($payType == 'WXZFWAP') {
            $this->tongdao = 'WxWap';
        } elseif ($payType == 'ALIPAY') {
            $this->tongdao = 'ZfbSm';
        } elseif ($payType == 'ALIPAYWAP') {
            $this->tongdao = 'ZfbWap';
        } else {
            $this->tongdao = '';
        }

        $this->params = [
            'pay_memberid'      => $this->pay_memberid,         //商户编号
            'pay_notifyurl'     => $this->pay_notifyurl,        //回调地址
            'pay_callbackurl'   => $this->pay_callbackurl,      //通知地址 no't sign
            'pay_orderid'       => $this->pay_orderid,          //订单号
            'pay_amount'        => $this->pay_amount,           //金额
            'pay_applydate'     => $this->pay_applydate,        //订单提交时间 如:2014-12-26 18:18:18
            'pay_bankcode'      => rtrim($this->pay_bankcode, 'WAP'),         //银行编号
        ];
        //签名
        ksort($this->params);
        $md5Str = '';
        foreach ($this->params as $key => $val) {
            $md5Str .= $key . "=>" . $val . "&";
        }
        $this->sign                     = strtoupper(md5($md5Str .'key='. $this->parterKey));
        $this->params['tongdao']        = $this->tongdao;              //调用通道编码 如:WeiXinWap
        $this->params['pay_md5sign']    = $this->sign;

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
        $sign = $params['sign'] ?? '';
        $data = [ //签名字段
            "memberid"      => $params["memberid"], // 商户ID
            "orderid"       => $params["orderid"], // 订单号
            "amount"        => $params["amount"], // 交易金额
            "datetime"      => $params["datetime"], // 交易时间
            "returncode"    => $params["returncode"]
        ];
        ksort($data);
        $md5Str = '';
        foreach ($data as $key => $val) {
            $md5Str .= $key . "=>" . $val . "&";
        }
        $mySign             = strtoupper(md5($md5Str .'key='. $this->parterKey));

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

}