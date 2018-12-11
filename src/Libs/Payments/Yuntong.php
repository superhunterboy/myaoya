<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Yuntong implements WMPay
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
            'W1WEIXIN_PAY' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            'Z2ALIPAY' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            'Q2TEN_PAY' => 'QQWAP',
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
    public $getwayUrl = 'http://47.95.111.61/req'; // 生产 http://47.95.111.61/req  测试 http://47.94.86.80/openapi/req

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;

    public $mchId;

    public $org;

    /**
     * 商户key
     * @var string
     */
    public $parterKey;

    public $pubKey;

    public $priKey;

    public $aesKey;

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
            $this->parterNo    = json_decode($conf['parterNo'], true); // {"org":"","mchId":""}
            $this->mchId       = $this->parterNo['mchId'];
            $this->org         = $this->parterNo['org'];
            $this->parterKey   = json_decode($conf['parterKey'], true); // {"aesKey":"","pubKey":"","priKey":""}
            $this->aesKey      = $this->parterKey['aesKey'];
            $this->pubKey      = $this->parterKey['pubKey'];
            $this->priKey      = $this->parterKey['priKey'];
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
        // 这个支付比较特殊，要用到发起充值请求的客户端IP地址
        $ipAndNo = explode('_', $orderId);

        $this->orderNo = $ipAndNo[0];

        $ipAddress = $ipAndNo[1];

        $reqParams = [
            'mchId'            => $this->mchId,
            'orderId'          => $this->orderNo, // md5($this->orderNo),
            'prodId'           => substr($payType, 0, 2), // Z2（支付宝H5），W1（微信H5，测试环境不可用，需等正式账号）
            'payChannel'       => substr($payType, 2),
            'tradeType'        => 'H5_WAP', // 只接手机端H5
            'ipAddress'        => $ipAddress,
            'feeType'          => 'CNY',
            'title'            => '充值',
            'body'             => '充值',
            'totalAmount'      => $money * 100, // 分
            'settlementPeriod' => 'T1', // D0账户余额：可实时提现金额； T1账户余额：不可实时提现金额，T+1日结算到D0账户或银行卡
            'notifyUrl'        => $this->callbackUrl . '/' . $this->orderNo, // /payment/yuntong/callback/201805171757547865754
            'jumpUrl'          => $this->notifyUrl,
            'appName'          => '澳亚国际',
            'osPlatform'       => 'AndroidWeb', // 安卓手机浏览器： AndroidWeb、IOS手机浏览器： IOSWeb
            'wapUrl'           => 'https://7001.com',
        ];

        $this->params = [
            'method'    => 'scanpay.order.create',
            'org'       => $this->org,
            'request'   => Utils::encryptAES(json_encode($reqParams), $this->aesKey),
            'timestamp' => Utils::getMillisecond(),
            'traceId'   => $this->orderNo,
        ];

        $this->params['sign'] = Utils::rsaSign_string(Utils::getXifuSignStr($this->params), $this->priKey);

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
            'json'   => $this->params,
            'verify' => false,
        ]);
        if ($res->getStatusCode() == '200') {
            $resData = $res->getBody()->getContents();
            $resData = json_decode($resData, true);
            if ($this->verifySign($resData)) {
                $result = Utils::decryptAES($resData['encryptData'], $this->aesKey);
                // {
                //     "actualPayAmount": 13000,
                //     "feeType": "CNY",
                //     "orderId": "201805171608568520856",
                //     "orgNo": "13020052",
                //     "payChannel": "ALIPAY",
                //     "resultCode": "SUCCESS",
                //     "totalAmount": 13000,
                //     "tradeId": 100000000000000488,
                //     "tradeState": "WAIT_PAY",
                //     "tradeType": "H5_WAP",
                //     "tradeUrl": "http://t.hqast.com/dist/h5pay/index.html?c=ALIPAY&k=88199f6b2d41af068d80fc61cb9fb7c0&t=1526544537993&s=09996c52b7de6690aea58a4248076a38"
                // }
                if (Utils::isJSON($result)) {
                    $resultArr = json_decode($result, true);
                    if ($resultArr['resultCode'] == 'SUCCESS' && $resultArr['tradeState'] == 'WAIT_PAY') {
                        return $resultArr['tradeUrl'];
                    } else {
                        return $result;
                    }
                } elseif (preg_match("/<.*<\/form>/", Utils::formatHtml($result))) {
                    // 只接了H5手机端支付，这句没测试，用到了再看
                    return $resData;
                } else {
                    return json_encode(['order' => $this->orderNo]);
                }
            }
        }
    }

    /**
     * 同步验签
     */
    public function verifySign($params = [])
    {
        return Utils::rsaVerify_string(Utils::getXifuSignStr($params), $this->pubKey, $params['sign']);
    }

    public function callbackVerifySign($params = [])
    {
        $sign        = $params['sign'];
        $encryptData = $params['encryptData'];
        if (Utils::rsaVerify_string($encryptData, $this->pubKey, $sign)) {
            // 解密
            return json_decode(Utils::decryptAES($encryptData, $this->aesKey), true);
        }
        return false;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }
}
