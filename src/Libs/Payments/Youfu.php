<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//27
class Youfu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '902'  => '微信',
        ],
        2 => [
            '903'  => '支付宝',
        ],
        3 => [
            //'907'  => '网银支付',
        ],
        4 => [
            '908'  => 'QQ钱包',
        ],
        5 => [
            '912'  => '京东扫码',
        ],
        6 => [
        ],
        7 => [
            '913' => '银联扫码',
        ],
        8 => [//微信WAP
            '901' => '微信WAP',
        ],
        9 => [//支付宝WAP
            '904' => '支付宝WAP',
        ],
        10 => [//QQWAP
            '905' => 'QQWAP',
        ],
        11 => [//京东WAP
            '910' => '京东WAP',
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    //Windows支付网关
    public $getwayUrl = 'http://www.yozpay.com/Pay_Index.html';

    public $pay_memberid = "10038";   //商户ID
    public $pay_orderid;    //订单号
    public $pay_amount;    //交易金额
    public $pay_bankcode;   //银行编码
    public $pay_applydate;  //订单时间
    public $pay_notifyurl;   //服务端返回地址
    public $pay_callbackurl;  //页面跳转返回地址
    public $md5key = "h26ov2w2ykxkixazktezkchsti2vzbje";   //商户密钥

    //提交参数
    public $params = [];

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
            $this->pay_memberid     = $conf['parterNo'];
            $this->md5key           = $conf['parterKey'];
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
        $this->pay_amount   = $money;
        $this->pay_orderid  = $orderId;
        $this->pay_bankcode = $payType;

        $this->params     = [
            "pay_memberid"      => $this->pay_memberid,
            "pay_orderid"       => 'Y'.$this->pay_orderid,
            "pay_amount"        => $this->pay_amount,
            "pay_applydate"     => date("Y-m-d H:i:s"),
            "pay_bankcode"      => $this->pay_bankcode,
            "pay_notifyurl"     => $this->pay_notifyurl,
            "pay_callbackurl"   => $this->pay_callbackurl,
        ];
        //签名
        ksort($this->params);
        $md5str = urldecode(http_build_query($this->params));
        //$sign = strtoupper(md5($md5str . "&key=" . $Md5key));
        $this->params["pay_md5sign"] = strtoupper(md5($md5str . "&key=" . $this->md5key));

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
        $sign    = $params['sign'] ?? '';
        $ReturnArray = [ // 返回字段
            "memberid"       => $params["memberid"], // 商户ID
            "orderid"        =>  $params["orderid"], // 订单号
            "amount"         =>  $params["amount"], // 交易金额
            "datetime"       =>  $params["datetime"], // 交易时间
            "transaction_id" =>  $params["transaction_id"], // 支付流水号
            "returncode"     => $params["returncode"],
        ];

        ksort($ReturnArray);
        $md5str = urldecode(http_build_query($ReturnArray));
        $mySign = strtoupper(md5($md5str . "&key=" . $this->md5key));

        if ($sign == $mySign) {
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