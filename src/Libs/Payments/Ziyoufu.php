<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Ziyoufu implements WMPay
{
    public $payType = [
        1 => [
            'weixin' => '微信扫码',
        ],
        2 => [
            'alipay' => '支付宝扫码',
        ],
        3 => [
        ],
        4 => [
        ],
        5 => [
        ],
        6 => [
        ],
        7 => [
        ],
        8 => [//微信WAP
        ],
        9 => [//支付宝WAP
        ],
        10 => [//QQWAP
        ],
        11 => [//京东WAP
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    public $getwayUrl = 'https://pay.swiftpass.cn/pay/gateway'; // http://m.zypay.net/api/pay/GetQRCode

    public $parterNo;

    public $parterKey;

    public $callbackUrl;

    public $notifyUrl;

    public $remark = 'CZ';

    public $service;

    public $timeStart;

    public $timeExpire;

    public $version = '2.0';

    public $sign = '';

    public $totalFee;

    public $outTradeNo;

    public $nonceStr;

    public $createIp = '127.0.0.1';

    public $params = [];

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

            $this->parterNo = $conf['parterNo'];

            $this->parterKey = $conf['parterKey'];

            // $this->remark        = $conf['remark'];

            $this->callbackUrl = $conf['callbackUrl'];

            $this->notifyUrl = $conf['notifyUrl'];

        }

    }

    private function __clone()
    {}

    public function getPayType()
    {
        return $this->payType;
    }

    public function signature($type, $money, $orderId)
    {
        $this->timeStart = date('YmdHis');

        $this->timeExpire = date('YmdHis', strtotime('+30minute'));

        $this->remark = $orderId;

        $this->outTradeNo = $orderId;

        $this->totalFee = $money * 100;

        $this->nonceStr = mt_rand(time(), time() + rand(10000, 99999));

        if (in_array($type, ['weixin', 'alipay'])) {

            $this->service = 'pay.' . $type . '.native';

        }

        $this->params = [
            'attach'        => $this->remark,
            'body'          => $this->remark,
            'mch_create_ip' => $this->createIp,
            'mch_id'        => $this->parterNo,
            'nonce_str'     => $this->nonceStr,
            'notify_url'    => $this->callbackUrl,
            'out_trade_no'  => $this->outTradeNo,
            'service'       => $this->service,
            'time_expire'   => $this->timeExpire,
            'time_start'    => $this->timeStart,
            'total_fee'     => $this->totalFee,
            'version'       => $this->version,
            'sign'          => $this->sign,
        ];

        $signPars = Utils::getSignStr($this->params);

        $signPars .= "key=" . $this->parterKey;

        $this->sign = strtoupper(md5($signPars));

        $this->params['sign'] = $this->sign;

        return $this;
    }

    public function payment()
    {
        $client = new Client();

        $res = $client->request('POST', $this->getwayUrl, [
            'verify'=>false,
            'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
            ],
            'body' => Utils::toXml($this->params)
        ]);

        if ($res->getStatusCode() == '200') {

            $returnXml = $res->getBody();

            if ($returnXml) {

                $returnArr = Utils::toArray($returnXml);

                if (Utils::isZiyoufuSign($returnArr, $this->parterKey)) {

                    if ($returnArr['status'] == 0 && $returnArr['result_code'] == 0) {

                        return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->outTradeNo . '&fee=' . sprintf("%.2f", $this->totalFee / 100) . '&qrcode=' . urlencode($returnArr['code_img_url']);

                    } else {
                        return json_encode(['order' => $this->outTradeNo]);
                    }

                }

            }

        }

    }

    public function verifySign($params = [])
    {
        if (Utils::isZiyoufuSign($params, $this->parterKey)) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {

    }
}
