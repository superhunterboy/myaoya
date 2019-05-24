<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;

class Wande implements WMPay
{
    /**  盈付
     *   支付类型
     */

    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            "8006" => "支付宝扫码",
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
            "8007" => "支付宝WAP",
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
    public $getwayUrl = 'https://pay.tronup.com/api/pay/create_order';

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
            'mchId'      => $this->parterNo,
            'productId'  => $payType,
            'mchOrderNo' => $orderId,
            'currency'   => 'cny',
            'amount'     => $money * 100,
            'notifyUrl'  => $this->callbackurl,
            'subject'    => 'subject',
            'body'       => 'body',
        ];

        $this->params['sign'] = $this->getSignStr($this->params);

        return $this;
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
            // $imgStr = str_replace(':', '%3A', $resArr['payParams']['codeUrl']);
            // $imgStr = str_replace('/', '%2F', $imgStr);
            // $imgStr = str_replace('?', '%3F', $imgStr);
            // $imgStr = str_replace('=', '%3D', $imgStr);
            // $imgStr = str_replace('{', '%7B', $imgStr);
            // $imgStr = str_replace('"', '%22', $imgStr);
            // $imgStr = str_replace('&', '%26', $imgStr);
            // $imgStr = str_replace(',', '%2C', $imgStr);
            // $imgStr = str_replace('}', '%7D', $imgStr);
            // $myImg  = 'https://pay.tronup.com/api/qrcode_img_get/?url=' . $imgStr . "&widht=200&height=200&codeurl";
            return $resArr['payParams']['codeImgUrl'];
        } else {
            return false;
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        foreach ($params as $k => $v) {
            if (!$v) {
                unset($params[$k]);
            }

        }
        $mySign = strtoupper($this->getSignStr($params));

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
        $msg = '';
        ksort($params);
        foreach ($params as $key => $value) {
            $msg .= $key . "=" . $value . "&";
        }
        $msg .= 'key=' . $this->key;
        return md5($msg);
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }
}
