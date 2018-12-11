<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Shundatong implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            '1' => '支付宝扫码',
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
    public $getwayUrl = 'http://47.75.252.1/wzapay-core/merchantPay/scanCodePay'; # 支付网关地址
    //public $getwayUrl = 'http://47.75.252.1/wzapay-manager/'; # 支付网关地址


    public $amount; #    价格
    public $callbackurl; #   下行异步通知地址
    public $hrefbackurl; #   下行同步通知地址(付款完毕后返回,可空):
    public $pubKey;
    public $priKey;

    /**
     * 提交参数
     * @var array
     */
    public $params = [];
    public $orderNo;

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
            $this->parter      = $conf['parterNo'];
            $parterKey         = json_decode($conf['parterKey'], true);
            $this->pubKey      = $parterKey['pubKey'];
            $this->priKey      = $parterKey['priKey'];
            $this->signPriKey  = $parterKey['signPriKey'];  //用于验证签名
            $this->callbackurl = $conf['callbackUrl'];
            $this->hrefbackurl = $conf['notifyUrl'];
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
        $this->amount = $money;
        $this->params = [
            'mchNo'      => $this->parter,
            'outTradeNo' => $orderId,
            'channel'    => $payType,
            'amount'     => $money,
            'title'      => 'supermarket',
            'body'       => 'VipCard',
            'payDate'    => date('YmdHis'),
            'notifyUrl'  => $this->callbackurl,
        ];
        ksort($this->params, SORT_STRING);
        $this->params['signStr'] = $this->getSignStr($this->params);
        $this->params['sign']    = Utils::rsaSign_string($this->params['signStr'], $this->priKey);
        file_put_contents("/var/www/html/aoya/logs/1.txt",var_export($this->params,true));
        unset($this->params['pubKey'], $this->params['signStr']);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();


        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);
        if ($res->getStatusCode() == '200') {
            $returnStr = $res->getBody()->getContents();
            if (Utils::isJSON($returnStr)) {
                $resArr = json_decode($returnStr, true);
                $code   = $resArr['resultCode'] ?? '';
                $msg    = $resArr['resultMsg'] ?? '';
                if ($code == '00') {
                    $payQRCode = $resArr['qrcode'] ?? '';
                    $qrcodeUrl = Utils::getQrcode($payQRCode);

                    echo 'http://pay.test/payment/scancode?trade_no=' . $resArr['outTradeNo'] . '&fee=' . $this->amount . '&qrcode=' . $qrcodeUrl . '&codeurl=' . $payQRCode;
                } else {
                    if (empty($code)) {
                        $code = 404;
                    }
                    return json_encode(['code' => $code, 'error' => $msg], JSON_UNESCAPED_UNICODE);
                }
            } else {
                return false;
            }
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
        ksort($params, SORT_STRING);
        $mySign  = $this->getSignStr($params);
        $newSign = Utils::rsaSign_string($mySign, $this->signPriKey);
        if ($sign === $newSign) {
            return ture;
        }
        return false;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    /**
     * getSignStr 签名生成
     */
    private function getSignStr($param)
    {
        $signStr = "";
        $tmpArr  = [];
        foreach ($param as $k => $v) {
            $tmpArr[] = $k . "=" . $v;
        }
        $signStr = implode('&', $tmpArr);
        $signStr .= '&signKey=' . $this->pubKey;
        $sign = md5($signStr);

        return $sign;
    }

}
