<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class WoPay implements WMPay
{
    /**  沃支付
     *   支付类型
     */

    public $paymentType = [
        1  => [ //微信扫码
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
            '0040' => '银联扫码',
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
            '0050' => '银联WAP',
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'http://channel.51feizhiyang.cn/agent/transReq.action';

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
        $this->money   = $money;
        $this->params  = [
            'transCode'   => '001',
            'service'     => $payType, // 请求类型 【微信：wxewm】
            'customerNo'  => $this->parterNo, // 商务号
            'externalId'  => $orderId, // 商户订单号
            'transAmount' => $money * 100, // 支付金额
            'reqDate'     => date("Ymd"),
            'reqTime'     => date("His"),
            'bgReturnUrl' => $this->callbackurl, // 异步通知地址
            'requestIp'   => '222.127.22.36', // 支付用户IP地址
        ];

        $this->params['sign'] = $this->getSignStr($this->params);

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
        // print_r(json_encode($this->params));die;
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, [
            'verify'  => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            ],
            'body'    => json_encode($this->params),
        ]);
        $body   = $res->getBody();
        $resArr = json_decode($body, true);
        // print_r($resArr);die;
        if ($res->getStatusCode() == '200') {
            $returnData = strval($res->getBody());
            $data       = json_decode($returnData, true);
            $returnCode = $data['code'] ?? 1;
            $returnMsg  = $data['message'] ?? '数据错误!';
            if ($returnCode != 10) {
                return json_encode(['code' => $returnCode, 'error' => $returnMsg], JSON_UNESCAPED_UNICODE);
            }
            $orderId = $data['externalId'] ?? '';
            $qrcode  = $data['payUrl'] ?? '';
            $money   = $data['tranAmt'] ?? $this->money;
            if (!empty($qrcode) && $orderId == $this->orderNo) {
                $qrcodeUrl = Utils::getQrcode($qrcode);
                // return $qrcodeUrl;
                return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money) . '&qrcode=' . urlencode($qrcodeUrl);
            } else {
                return json_encode(['code' => '1', 'error' => '数据错误!'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode(['code' => '1', 'error' => '网络异常!'], JSON_UNESCAPED_UNICODE);
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $mySign = $this->getSignStr($params);
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
            $msg .= $key . '=' . $value . '&';
        }
        $msg .= 'key=' . $this->key;

        return strtoupper(md5($msg));
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }
}
