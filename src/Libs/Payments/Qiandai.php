<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//平台22
class Qiandai implements WMPay
{
    public $payType = [
        1 => [
            '902' => '微信扫码',
        ],
        2 => [
            //'903' => '支付宝扫码', 暂无
        ],
        3 => [
            //'907' => '银行收银台', 暂无
        ],
        4 => [
        ],
        5 => [
        ],
        6 => [
        ],
        7 => [
        ],
        8 => [//微信WAP
            '901' => '微信WAP',
        ],
        9 => [//支付宝WAP
            '904' => '支付宝WAP',
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

    public $getwayUrl = 'http://www.mdkpay.com/Pay_Index.html';

    public $pay_memberid;    //商户号 是   是   平台分配商户号
    public $parterKey;       //商户key
    public $pay_orderid;     //订单号 是   是   上送订单号唯一, 字符长度20
    public $pay_applydate;   //提交时间    是   是   时间格式：2016-12-26 18:18:18
    public $pay_bankcode;    //银行编码    是   是   参考后续说明
    public $pay_notifyurl;   //服务端通知   是   是   服务端返回地址.（POST返回数据）
    public $pay_callbackurl; //页面跳转通知  是   是   页面跳转返回地址（POST返回数据）
    public $pay_amount;      //订单金额    是   是   商品金额
    public $pay_md5sign;     //MD5签名   是   否   请看MD5签名字段格式
    public $pay_productname; //商品名称    是   否

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
            $this->pay_memberid = $conf['parterNo'];
            $this->parterKey = $conf['parterKey'];
            $this->pay_notifyurl = $conf['callbackUrl'];
            $this->pay_callbackurl = $conf['notifyUrl'];
        }

    }

    private function __clone()
    {}

    public function getPayType()
    {
        return $this->payType;
    }

    public function signature($type, $money, $orderId)
    {
        $this->pay_orderid     = $orderId;
        $this->pay_productname = $orderId;
        $this->pay_amount      = $money;
        $this->pay_bankcode    = $type;

        $this->params = [
        'pay_memberid'      => $this->pay_memberid,    //商户号 是   是   平台分配商户号
        'pay_orderid'       => $this->pay_orderid,     //订单号 是   是   上送订单号唯一, 字符长度20
        'pay_applydate'     => date('Y-m-d H:i:s'),   //提交时间    是   是   时间格式：2016-12-26 18:18:18
        'pay_bankcode'      => $this->pay_bankcode,    //银行编码    是   是   参考后续说明
        'pay_notifyurl'     => $this->pay_notifyurl,   //服务端通知   是   是   服务端返回地址.（POST返回数据）
        'pay_callbackurl'   => $this->pay_callbackurl, //页面跳转通知  是   是   页面跳转返回地址（POST返回数据）
        'pay_amount'        => $this->pay_amount,      //订单金额    是   是   商品金额
        ];
        //不参与签名字段
        ksort($this->params);
        $signPars = urldecode(http_build_query($this->params)) . "&key=" . $this->parterKey;
        $this->params['pay_md5sign']     = strtoupper(md5($signPars));
        $this->params['pay_productname'] = $this->pay_productname;

        return $this;
    }

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

    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['attach']);
        ksort($params);
        $signStr = urldecode(http_build_query($params)) . "&key=" . $this->parterKey;
        $mySign = strtoupper(md5($signStr));

        if ($sign == $mySign) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {

    }
}