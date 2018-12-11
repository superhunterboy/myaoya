<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Hengtong implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            '1000' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            '01003' => '支付宝扫码',
        ],
        3  => [ //网银
            '962' => '中信银行',
            '963' => '中国银行',
            '964' => '中国农业银行',
            '965' => '中国建设银行',
            '967' => '中国工商银行',
            '970' => '招商银行',
            '971' => '邮政储蓄',
            '972' => '兴业银行',
            '977' => '上海浦东发展银行',
            '980' => '民生银行',
            '981' => '交通银行',
            '985' => '广东发展银行',
            '986' => '光大银行',
            '989' => '北京银行',
            '990' => '平安银行',
            '991' => '华夏银行',
            '992' => '上海银行',
        ],
        4  => [ //QQ扫码
            '01005' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            '0001007' => '京东扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            '001009' => '银联扫码',
        ],
        8  => [ //微信WAP
            '1002' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            '01004' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            '001006' => 'QQWAP',
        ],
        11 => [ //京东WAP
            '001008' => '京东WAP',
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
            '1012' => '银联WAP',
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://gateway.htpays.com/GateWay/Index';

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;

    public $payType;

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
        $this->payType = intval($payType);

        $this->params = [
            'customer'     => $this->parterNo,
            'banktype'     => $this->payType,
            'amount'       => sprintf("%.2f", $money), // 元
            'orderid'      => $this->orderNo,
            'asynbackurl'  => $this->callbackUrl,
            'request_time' => date('YmdHis'),
            'synbackurl'   => $this->notifyUrl,
            'isqrcode'     => 'N',
            'israndom'     => 'Y',
            'attach'       => 'CZ',
        ];

        $this->params['sign'] = $this->getSignString($this->params, $this->parterKey);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        return $this->htmlSend($this->getwayUrl, $this->params);
    }

    public function verifySign($params = [])
    {
        $sign          = $params['sign'] ?? '';
        $orderid       = $params['orderid'] ?? '';
        $result        = $params['result'] ?? '';
        $amount        = $params['amount'] ?? '';
        $systemorderid = $params['systemorderid'] ?? '';
        $completetime  = $params['completetime'] ?? '';
        return $sign == strtolower(md5("orderid={$orderid}&result={$result}&amount={$amount}&systemorderid={$systemorderid}&completetime={$completetime}&key={$this->parterKey}"));
    }

    public function getSignString($params = [], $key = '')
    {
        $customer     = $params['customer'];
        $banktype     = $params['banktype'];
        $amount       = $params['amount'];
        $orderid      = $params['orderid'];
        $asynbackurl  = $params['asynbackurl'];
        $request_time = $params['request_time'];
        return strtolower(md5("customer={$customer}&banktype={$banktype}&amount={$amount}&orderid={$orderid}&asynbackurl={$asynbackurl}&request_time={$request_time}&key={$key}"));
    }

    public function query($orderId)
    {
    }

    private function htmlSend($url, $data, $method = 'post')
    {
        if ($method == 'get') {
            return $url . '?' . http_build_query($data);
        } elseif ($method == 'post') {
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $url . '" method="post" name="orderForm">';
            foreach ($data as $key => $value) {
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
    }
}
