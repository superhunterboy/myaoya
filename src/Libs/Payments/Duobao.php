<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Duobao implements WMPay
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
            '962'  => '中信银行',
            '963'  => '中国银行',
            '964'  => '中国农业银行',
            '965'  => '中国建设银行',
            '967'  => '中国工商银行',
            '970'  => '招商银行',
            '971'  => '中国邮政储蓄银行',
            '972'  => '兴业银行',
            '977'  => '浦东发展银行',
            '978'  => '平安银行',
            '980'  => '民生银行',
            '981'  => '交通银行',
            '982'  => '华夏银行',
            '985'  => '广东发展银行',
            '986'  => '光大银行',
            '1962' => '网银快捷',
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
    public $getwayUrl = 'https://gwbb69.169.cc/interface/AutoBank/index.aspx';

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

        $this->params  = [
            'parter' => $this->parterNo,
            'type' => $payType,
            'value' => sprintf("%.2f", $money),
            'orderid' => $this->orderNo,
            'callbackurl' => $this->callbackUrl,
            'hrefbackurl' => $this->notifyUrl,
            'attach' => '',
        ];

        $this->params['sign'] = $this->getSignStr($this->params);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        // print_r($this->params);
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, [
            'form_params' => $this->params,
            'verify' => false,
        ]);
        if ($res->getStatusCode() == '200') {
            $resData = $res->getBody()->getContents();
            // echo $resData;
            if (preg_match("/<.*<\/form>/", Utils::formatHtml($resData))) {
                // 返回表单，自动POST提交
                return $resData;
            } else {
                return json_encode(['order' => $this->orderNo]);
            }
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        $signStr = '';
        foreach ($params as $k => $v) {
            if (!in_array($k, ['hrefbackurl', 'attach', 'sign'])) {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        // echo rtrim($signStr, '&') . $this->parterKey;
        return strtolower(md5(rtrim($signStr, '&') . $this->parterKey));
    }

}
