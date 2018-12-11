<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//32
class Dianfuyun implements WMPay
{
    // 支付类型
    public $paymentType = [
        1 => [ //微信扫码
            'WxSm'   => '微信',
        ],
        2 => [ //支付宝扫码
            'DFYzfb' => '支付宝',
        ],
        3 => [ //网银
            'CMB'   => '招商银行',
            'ICBC'  => '工商银行',
            'CCB'   => '建设银行',
            'SPDB'  => '浦发银行',
            'ABC'   => '农业银行',
            'CMBC'  => '民生银行',
            'SDB'   => '深发银行',
            'CIB'   => '兴业银行',
            'BOCOM' => '交通银行',
            'CEB'   => '光大银行',
            'BOC'   => '中国银行',
            'PAB'   => '平安银行',
            'GDB'   => '广发银行',
            'CITIC' => '中信银行',
            'NBCB'  => '宁波银行',
            'HXBC'  => '华夏银行',
            'PSBC'  => '邮政储蓄',
            'BOBJ'  => '北京银行',
            'BOS'   => '上海银行',
            'NJCB'  => '南京银行',
        ],
        4 => [ //QQ扫码
        ],
        5 => [ //京东扫码
            'JdPay' => '京东扫码',
        ],
        6 => [ //百度扫码
        ],
        7 => [ //银联扫码
        ],
        8 => [//微信WAP
        ],
        9 => [//支付宝WAP
            'ZfbWap' => '支付宝WAP',
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
    public $getwayUrl = 'http://www.919km.cn/Pay_Index.html'; //old: http://www.adsstore.cn/Pay_Index.html

    //请求参数
    public $pay_memberid;       //商户编号
    public $Md5key;             //商户md5key
    public $pay_notifyurl;      //回调地址
    public $pay_callbackurl;    //通知地址

    public $pay_orderid;    //订单号
    public $pay_amount;     //交易金额
    public $pay_applydate;  //订单时间
    public $pay_bankcode;   //银行编码
    public $tongdao;        //通道
    public $pay_md5sign;    //签名
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
            $this->Md5key           = $conf['parterKey'];
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
        $this->tongdao          = $payType;
        $this->pay_amount       = $money;
        $this->pay_orderid      = $orderId;
        $this->pay_applydate    = date('Y-m-d H:i:s');
        if ($payType == 'WxSm') {
            $this->pay_bankcode = 'WXZF';
        } elseif ($payType == 'DFYzfb') {
            $this->pay_bankcode = 'ALIPAY';
        } elseif ($payType == 'ZfbWap') {
            $this->pay_bankcode = 'ALIPAY';
        } elseif ($payType == 'JdPay') {
            $this->pay_bankcode = 'JDZF';
        } else {
            $this->pay_bankcode = $payType;
            $this->tongdao      = 'Gopay';
        }

        $this->params = [
            "pay_memberid"      => $this->pay_memberid,
            "pay_orderid"       => $this->pay_orderid,
            "pay_amount"        => $this->pay_amount,
            "pay_applydate"     => $this->pay_applydate,
            "pay_bankcode"      => $this->pay_bankcode,
            "pay_notifyurl"     => $this->pay_notifyurl,
            "pay_callbackurl"   => $this->pay_callbackurl
        ];
        //签名
        ksort($this->params);
        $md5str = '';
        foreach ($this->params as $key => $val) {
            $md5str .= $key . "=>" . $val . "&";
        }
        $this->pay_md5sign              = strtoupper(md5($md5str . "key=" . $this->Md5key));
        $this->params["pay_md5sign"]    = $this->pay_md5sign;
        $this->params["tongdao"]        = $this->tongdao;

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
        unset($params['sign']);
        $ReturnArray = [
            "memberid"   => $params["memberid"], // 商户ID
            "orderid"    => $params["orderid"], // 订单号
            "amount"     => $params["amount"], // 交易金额
            "datetime"   => $params["datetime"], // 交易时间
            "returncode" => $params["returncode"]
        ];
        ksort($ReturnArray);
        $md5str = '';
        foreach ($ReturnArray as $key => $val) {
            $md5str .= $key . "=>" . $val . "&";
        }
        $mySign = strtoupper(md5($md5str . "key=" . $this->Md5key));

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