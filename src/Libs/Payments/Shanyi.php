<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Shanyi implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'WX' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'ZFB' => '支付宝扫码',
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'QQ' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            'JD' => '京东扫码',
        ],
        6  => [ //百度扫码
            'BAIDU' => '百度扫码',
        ],
        7  => [ //银联扫码
            'UNION_WALLET' => '银联扫码',
        ],
        8  => [ //微信WAP
            'WX_WAP' => '微信WAP',
        ],
        9  => [ //支付宝WAP
            'ZFB_WAP' => '支付宝WAP',
        ],
        10 => [ //QQWAP
            'QQ_WAP' => 'QQWAP',
        ],
        11 => [ //京东WAP
            'JD_WAP' => '京东WAP',
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
            'UNION_WALLET_H5' => '银联WAP',
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'http://%s.637pay.com/api/pay';

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

    public $pubKey;

    public $priKey;

    public $md5Key;

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
            $this->parterKey   = json_decode($conf['parterKey'], true); // {"md5Key":"","pubKey":"","priKey":""}
            $this->md5Key      = $this->parterKey['md5Key'];
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
        $this->orderNo = $orderId;
        $this->payType = $payType;

        $money = $money * 100; // 分

        $reqParams = [
            'version'         => 'V4.0.0.0',
            'charset'         => 'UTF-8',
            'merNo'           => $this->parterNo,
            'subMerNo'        => $this->parterNo,
            'netway'          => $this->payType,
            'random'          => (string) mt_rand(1000, 9999),
            'orderNum'        => $this->orderNo,
            'amount'          => (string) $money,
            'goodsName'       => '充值',
            'callBackUrl'     => $this->callbackUrl,
            'callBackViewUrl' => $this->notifyUrl,
        ];

        $reqParams['sign'] = Utils::getSignString($reqParams, $this->md5Key);

        print_r($reqParams);

        $this->params = [
            'data'    => Utils::enCryptByRSA(json_encode($reqParams, 320), $this->pubKey),
            'merchNo' => $this->parterNo,
            'version' => 'V4.0.0.0',
        ];

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        // print_r($this->params);
        $tempArr = [
            'WX'              => 'wx',
            'WX_H5'           => 'wx',
            'WX_WAP'          => 'wxwap',
            'ZFB'             => 'zfb',
            'ZFB_WAP'         => 'zfbwap',
            'QQ'              => 'qq',
            'QQ_WAP'          => 'qqwap',
            'JD'              => 'jd',
            'JD_WAP'          => 'jd',
            'BAIDU'           => 'baidu',
            'UNION_WALLET'    => 'unionpay',
            'UNION_WALLET_H5' => 'unionpay',
            'MBANK'           => 'mbank',
        ];
        $client = new Client();
        $res    = $client->request('POST', sprintf($this->getwayUrl, $tempArr[$this->payType]), [
            'headers'     => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36',
            ],
            'form_params' => $this->params,
            'verify'      => false,
            'debug'       => true,
        ]);
        if ($res->getStatusCode() == '200') {
            $resData = $res->getBody()->getContents();
            $resData = json_decode($resData, true);
            print_r($resData);
            // Array
            // (
            //     [merNo] => SYF201805030857
            //     [msg] => 提交成功
            //     [orderNum] => 2018060511522522215
            //     [qrcodeUrl] => http://desk.ling-pay.com/h5pay/PrePayServlet?payType=JD&orderNum=482018060511522951959038
            //     [sign] => 8E0857920D14C9D0B013EDA408748C05
            //     [stateCode] => 00
            // )
            if ($this->verifySign($resData)) {
                if ($resData['stateCode'] == '00') {
                    return $resData['qrcodeUrl'];
                }
            }
            if (!isset($resData['order'])) {
                $resData['order'] = $this->orderNo;
            }
            return json_encode($resData);
        }
    }

    public function verifySign($params = [])
    {
        if (isset($params['sign'])) {
            $sign = $params['sign'];
            unset($params['sign']);
            return ksort($params) ? strtoupper(md5(json_encode($params, 320) . $this->md5Key)) === $sign : false;
        }
        return false;
    }

    public function callbackVerifySign($params = [])
    {
    }

    public function query($orderId)
    {
    }
}
