<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//33
class Xinfu implements WMPay
{
    // 支付类型
    public $paymentType = [
        1 => [ //微信扫码
            '1004'     => '微信',
        ],
        2 => [ //支付宝扫码
            '01003'     => '支付宝',
        ],
        3 => [ //网银
            '01020000' => '工商银行',
            '01040000' => '中国银行',
            '03010000' => '交通银行',
            '01030000' => '农业银行',
            '01050000' => '建设银行',
            '03020000' => '中信银行',
            '03070000' => '平安银行',
            '03050000' => '民生银行',
            '03060000' => '广发银行',
            '03090000' => '兴业银行',
            '03100000' => '浦发银行',
            '03080000' => '招商银行',
            '03030000' => '光大银行',
            '03040000' => '华夏银行',
            '04030000' => '邮政储蓄',
        ],
        4 => [ //QQ扫码
        ],
        5 => [ //京东扫码
        ],
        6 => [ //百度扫码
        ],
        7 => [ //银联扫码
        ],
        8 => [//微信WAP
        ],
        9 => [//支付宝WAP
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
    public $getwayUrl = 'http://121.40.168.196:8088/icpay-sea/netrecv/merchant/recvMerchant.html';  //测试地址

    //请求参数
    public $cust_id;            //商户编号
    public $parterKey;          //商户md5key
    public $callbackUrl;        //回调地址 没有实际用到,实际回调跟芯富协商
    public $ret_url;            //通知地址

    public $version = '01';     //版本号  目前固定为01
    public $ord_id;             //订单号
    public $subject = 'smart';  //商品名称 应使用UTF-8字符集URLEncode编码后传入
    public $gate_id;            //支付渠道  1001：网银支付 1002：快捷支付 1003：支付宝 1004：微信 1008：大商户银联在线
    public $trans_amt;          //交易金额  格式为 1.23
    public $bg_ret_url = 'http://121.40.168.196:8088/icpay-sea/netrecv/merchant/webAsynMessage';  //测试地址
    public $check_value;        //定长32位  签名  必须
    //card_type 支付卡类型  可选  CR01 - 信用卡支付 DT01 - 借记卡支付
    public $bank_id;            //直连银行号
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
            $this->cust_id      = $conf['parterNo'];
            $this->parterKey    = $conf['parterKey'];
            $this->callbackUrl  = $conf['callbackUrl'];
            $this->ret_url      = $conf['notifyUrl'];
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
        $this->gate_id      = $payType;
        $this->trans_amt    = sprintf("%.2f", $money);
        $this->ord_id       = substr($orderId, 4);
        if (!in_array($payType, ['01003', '1004'])) {
            $this->bank_id = $payType;
            $this->gate_id = '1001';
        }
        $this->bank_id = $this->bank_id ?? '';

        $this->params = [
            'version'    => $this->version,
            'cust_id'    => $this->cust_id,
            'ord_id'     => $this->ord_id,
            'subject'    => urlencode($this->subject),
            'gate_id'    => $this->gate_id,
            'trans_amt'  => $this->trans_amt,
            'ret_url'    => urlencode($this->ret_url),
            'bg_ret_url' => urlencode($this->bg_ret_url),
            'bank_id'    => $this->bank_id,
        ];
        //签名
        $signStr = '';
        foreach($this->params as $value){
            $signStr .= $value;
        }
        $signStr .= $this->parterKey;
        $this->check_value              = strtoupper(md5($signStr));
        $this->params["check_value"]    = $this->check_value;

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
        $sign = $params['check_value'] ?? '';
        unset($params['check_value']);
        if (isset($params['notify_id'])) {
            unset($params['notify_id']);
        }
        $order = [
            'resp_code','resp_desc','cust_id','ord_id','platform_seq_id','trans_amt','mer_priv','extension'
         ];
        $signStr = '';
        foreach($order as $value){
            if (isset($params[$value]) && !empty($params[$value])) {
                $signStr .= $params[$value];
            }
        }
        $signStr .= $this->parterKey;
        $mySign   = strtoupper(md5($signStr));

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

}