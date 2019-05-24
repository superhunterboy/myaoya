<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;

class WenPay implements WMPay
{
    /**  盈付
     *   支付类型
     */

    public $paymentType = [
        1  => [ //微信扫码
            'wxewm' => '微信扫码',
        ],
        2  => [ //支付宝扫码
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
            'wxewm' => '微信WAP',
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
    public $getwayUrl = 'https://wenfupay.yuyezhixin.com/Pay';

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
            $this->parterNo    = $conf['parterNo'];
            $this->key         = $conf['parterKey']; // {"pubKey":"...","priKey":"..."}
            $this->callbackurl = $conf['callbackUrl'];
            $this->notify      = $conf['notifyUrl'];
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

        $this->params = [
            'fxid'        => $this->parterNo, // 商务号
            'fxddh'       => $orderId, // 商户订单号
            'fxdesc'      => 'test', // 商品名称
            'fxfee'       => $money, // 支付金额
            'fxnotifyurl' => $this->callbackurl, // 异步通知地址
            'fxbackurl'   => $this->notify, // 同步通知地址
            'fxpay'       => $payType, // 请求类型 【微信：wxewm】
            'fxip'        => $this->getClientIP(), // 支付用户IP地址
        ];

        $this->params['fxsign'] = $this->getSignStr($this->params);

        return $this;
    }

    public function getClientIP($type = 0, $adv = false)
    {
        global $ip;
        $type = $type ? 1 : 0;
        if ($ip !== null) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }

                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array(
            $ip,
            $long) : array(
            '0.0.0.0',
            0);
        return $ip[$type];
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        // print_r($this->params);die;
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params, 'verify' => false]);
        $body   = $res->getBody();
        $resArr = json_decode($body, true);
        if ($resArr) {
            return $resArr['payurl'];
        } else {
            return false;
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $mySign = md5($params['fxstatus'] . $params["fxid"] . $params["fxddh"] . $params["fxfee"] . $this->key); //加密
        $sign   = $params['fxsign'];
        if ($sign === $mySign) {
            return true;
        }
        return false;
    }

    /**
     * getSignStr 签名生成
     */
    private function getSignStr($params)
    {
        $msg = md5($params["fxid"] . $params["fxddh"] . $params["fxfee"] . $params["fxnotifyurl"] . $this->key); //加密

        return $msg;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }
}
