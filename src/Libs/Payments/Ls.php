<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Ls implements WMPay
{
    /*
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            'alpls'=>'支付宝'
        ],
        3  => [ //网银
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
            'alpls'=>'支付宝WAP'
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
    public $getwayUrl = 'http://202.53.137.101/service/receiveOrder';

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

        $this->params['orderId']         =$this->orderNo;
        $this->params['appId']           =$this->parterNo;
        $this->params['amount']          =$this->money;
        $this->params['remark']          ="2ww";
        $this->params['time']            =date("Y-m-d H:i:s",time());
        $this->params['notify']          =$this->callbackUrl;
        $this->params['sign']            =$this->getSignStr($this->params,$this->parterKey);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params,'verify' => false]);
        $body = $res->getBody();
        $resArr = json_decode($body, true);
        return $resArr;
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];

        unset($params['sign']);
        $returnsign=$this->getSignStr($params,$this->parterKey);

        return $sign == $returnsign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [], $paysecret = '')
    {
        $array['secret']=$paysecret;
        ksort($array);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg = $msg . "$key=$val&";
        }
        $msg = substr($msg,0,strlen($msg)-1);
        return md5($msg);
    }
}
