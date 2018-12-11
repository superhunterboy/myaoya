<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Ylsl implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            '2' => '支付宝扫码',
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
    public $getwayUrl = 'http://pay.cjs6.com:8123/gateway/OrderPayV3';

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
            $this->parterNo  = $conf['parterNo'];
            $this->parterKey = $conf['parterKey'];
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
        $this->money   = $money * 100;  //分

        $this->params = [
            'merId'             => $this->parterNo,
            'businessOrderId'   => $this->orderNo,
            'orderName'         => $this->orderNo,
            'payType'           => $payType,
            'selfParam'        => "sn".$this->orderNo,
            'asynURL'           => $this->callbackUrl,
            'tradeMoney'        => $this->money
        ];

        $temp = $this->getSignStr($this->params);

        $this->params['Sign'] = $temp;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        //print_r($this->params);die;
        $client = new Client();

        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params,'verify' => false]);
        $body    = $res->getBody();

        if (Utils::isJSON($body)) {
            $resArr = json_decode($body, true);
            if ($resArr['msg'] == 'OK' && $resArr['code'] == '1000') {
                $payQRCode    = $resArr['code'] ?? '';
                $payQRCodeUrl = $resArr['info']['codeurl'] ?? '';
                $qrcodeUrl    = Utils::getQrcode($payQRCodeUrl);
                //return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
                return 'http://www.aoya.local/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);

            } else {
                return json_encode(['order' => $this->orderNo]);
            }
        } else {
            return json_encode(['order' => $this->orderNo]);
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($array = [])
    {
        $sign=$array['sign'];
        unset($array['sign']);

        ksort($array);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg .= $key.$val;
        }

        //$msg = $msg . $this->parterKey;
        echo md5(strtolower($msg))."--------".$sign;
        die();
        return md5(strtolower($msg)) == $array['sign'];
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [])
    {
        ksort($array);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg .= $key.$val;
        }

        $msg = $msg . $this->parterKey;
        return md5(strtolower($msg));
    }
}
