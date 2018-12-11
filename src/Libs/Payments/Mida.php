<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Mida implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'wxpay' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'alipay' => '支付宝扫码',
        ],
        3  => [ //网银
            'ICBC'  => '中国工商银行',
            'ABC'   => '中国农业银行',
            'BOCSH' => '中国银行',
            'CCB'   => '建设银行',
            'CMB'   => '招商银行',
            'SPDB'  => '浦发银行',
            'GDB'   => '广发银行',
            'BOCOM' => '交通银行',
            'PSBC'  => '邮政储蓄银行',
            'CNCB'  => '中信银行',
            'CMBC'  => '民生银行',
            'CEB'   => '光大银行',
            'HXB'   => '华夏银行',
            'CIB'   => '兴业银行',
            'BOS'   => '上海银行',
            'SRCB'  => '上海农商',
            'PAB'   => '平安银行',
            'BCCB'  => '北京银行',
        ],
        4  => [ //QQ扫码
            'qqpay' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            'jdpay' => '京东扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
        ],
        8  => [ //微信WAP
            'wxwap' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            'aliwap' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            'qqwap' => 'QQWAP',
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
    public $getwayUrl = 'http://top.midadacn.cn/mida/pay';

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
     * 提交参数
     * @var array
     */
    public $params = [];

    public $payType;

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
    {
    }

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
        $this->payType = $payType;
        $this->orderNo = $orderId;
        $this->money   = sprintf("%.2f", $money);

        if (!in_array($this->payType, ['wxpay', 'wxwap', 'alipay', 'aliwap', 'qqpay', 'qqwap', 'jdpay', 'bankwap', 'kjpay'])) {
            $this->payType = 'bank';
        } else {
            $payType = '';
        }

        $this->params = [
            'version'    => '1.0',
            'customerid' => $this->parterNo,
            'sdorderno'  => $this->orderNo,
            'total_fee'  => $this->money,
            'paytype'    => $this->payType,
            'bankcode'   => $payType,
            'notifyurl'  => $this->callbackUrl,
            'returnurl'  => $this->notifyUrl,
            'remark'     => '',
            'get_code'   => 1,
            'sign'       => '',
        ];

        $this->params['sign'] = md5("version=1.0&customerid={$this->parterNo}&total_fee={$this->money}&sdorderno={$this->orderNo}&notifyurl={$this->callbackUrl}&returnurl={$this->notifyUrl}&{$this->parterKey}");

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        $result = $client->request('POST', $this->getwayUrl, [
            'verify'      => false,
            'headers'     => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            ],
            'form_params' => $this->params,
        ]);
        if ($result->getStatusCode() == '200') {
            // What the fuck！！！返回的全部都是html自动提交的表单
            return $result->getBody()->getContents();
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $signStr = "customerid={$params['customerid']}&status={$params['status']}&sdpayno={$params['sdpayno']}&sdorderno={$params['sdorderno']}&total_fee={$params['total_fee']}&paytype={$params['paytype']}&{$this->parterKey}";
        return $params['sign'] == md5($signStr);
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }
}
