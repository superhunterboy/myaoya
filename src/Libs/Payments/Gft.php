<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Gft implements WMPay
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
            '1001' => '工商银行',
            '1002' => '中国银行',
            '1003' => '招商银行',
            '1004' => '建设银行',
            '1005' => '交通银行',
            '1006' => '农业银行',
            '1007' => '邮政储蓄银行',
            '1008' => '中信银行',
            '1009' => '民生银行',
            '1010' => '浦发银行',
            '1011' => '光大银行',
            '1012' => '平安银行',
            '1013' => '兴业银行',
            '1014' => '华夏银行',
            '1015' => '北京银行',
            '1016' => '上海银行',
            '1017' => '广发银行',
            '1018' => '北京农村商业银行',
            '1019' => '晋中银行',
            '1020' => '杭州银行',
            '1021' => '宁波银行',
            '1022' => '深圳发展银行',
            '1023' => '农村信用社',
            '1024' => '徽商银行',
            '1025' => '渤海银行',
            '1026' => '重庆三峡银行',
            '1027' => '长沙银行',
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
    public $getwayUrl = 'http://www.51gftpay.com/gateway/api/orderPay';

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
    public $pubKey;
    public $priKey;
    public $payke;
    public $paysecret;

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
     * 签名
     * @var string
     */
    public $sign;

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
            $this->parterNo = $conf['parterNo'];
            $this->parterKey = $conf['parterKey']; // {"pubKey":"...","priKey":"..."}

            $this->banknumber = isset($conf['banknumber'])?$conf['banknumber']:"";

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
        $this->money   = $money*100;
        $res=array_key_exists($payType,$this->paymentType[3]);
        if($res){
            $payType='014';
        }

        $this->params['amount']             =$this->money;
        $this->params['bizContent']         =$this->banknumber;
        $this->params['version']            ="V001";
        $this->params['commodityName']      ='abc'.time().rand(10000,99999);
        $this->params['backNotifyUrl']      =$this->callbackUrl;
        $this->params['mchId']              =$this->parterNo;
        $this->params['payType']            =$payType;
        $this->params['frontNotifyUrl']     =$this->callbackUrl;
        $this->params['timestamp']          =time();
        $this->params['mchOrderNo']         =$this->orderNo;

        $this->params['sign']=$this->getSignStr($this->params,$this->parterKey);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $this->getwayUrl);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_SSLVERSION, 1 );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
            'Content-type:application/json;charset=UTF-8'
        ));
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ($this->params, JSON_UNESCAPED_UNICODE ) );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $resp = curl_exec ( $ch );
        $result=json_decode($resp, true);
        if($result['code'] == 200){
            return $result['data']['qr_code'];
        }
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];

        unset($params['sign']);
        $returnsign=$this->getSignStr($params,$this->parterKey);
        return strtoupper($sign) == $returnsign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [], $paysecret = '')
    {
        ksort($array);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg = $msg . "$key=$val&";
        }
        $msg = $msg ."key=".$paysecret;
        return strtoupper(md5($msg));
    }
}
