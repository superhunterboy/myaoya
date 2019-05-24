<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Aibide implements WMPay
{
    /**  盈付
     *   支付类型
     */

    public $paymentType = [
        1  => [ //微信扫码
            'weixin' => '微信扫码',
        ],
        2  => [ //支付宝扫码
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'qqrcode' => 'QQ扫码',
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
            'alipaywap' => '支付宝扫码',
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
    public $getwayUrl = 'http://www.gfdhqn.cn/port/firstsubmit.php';

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
            $this->parterKey   = $conf['parterKey'];
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

        $this->params["userid"]    = $this->parterNo;
        $this->params["orderid"]   = $this->orderNo;
        $this->params["total_fee"] = $this->money * 100;
        $this->params["body"]      = 'zhifuceshi';
        $this->params["paytype"]   = $payType;
        $this->params["notifyUrl"] = 'https://www.baidu.com';
        $this->params["returnUrl"] = 'https://www.baidu.com';
        $this->params["clientIp"]  = $_SERVER['REMOTE_ADDR'] ?? '47.91.249.137';
        ksort($this->params);
        $md5str = '';
        foreach ($this->params as $key => $val) {
            if ($key == 'notifyUrl' || $key == 'returnUrl') {
                $md5str = $md5str . $key . "=" . urlencode($val) . "&";
            } else {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $signStr = $md5str."key=".$this->parterKey;
        $this->params["sign"] = md5($signStr);
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params, 'verify' => false]);
        $body = $res->getBody();
        $resArr = json_decode($body, true);
        return $resArr['data']['url'];
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        // {
        //     "memberid":"10105",
        //     "orderid":"201904250754168078238",
        //     "transaction_id":"yf20190425155446549756",
        //     "amount":"20.0000",
        //     "datetime":"20190425155737",
        //     "returncode":"00",
        //     "sign":"17654D58BEC9AFF4BC05A699D06130AA",
        //     "attach":"1234|456"
        // }
        $msg  = '';
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['attach']);
        ksort($params);
        // reset($params);
        foreach ($params as $key => $value) {
            $msg .= $key . "=" . $value . "&";
        }
        // $msg = substr($msg, 0, (strlen($msg) - 1));

        $mySign = strtoupper(md5($msg . 'key=' . $this->parterKey));

        return $sign == $mySign;

    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [], $paysecret = '')
    {
        $msg = "parter=" . $array['parter'] . "&type=" . $array['type'] . "&orderid=" . $array['orderid'] . "&callbackurl=" . $this->callbackUrl . $paysecret;
        return md5($msg);
    }
}
