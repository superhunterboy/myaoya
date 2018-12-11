<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Wallet implements WMPay
{
    public $payType = [
        1 => [
            '12' => '微信扫码',
        ],
        2 => [
            '30' => '支付宝扫码',
        ],
        3 => [
            'BOC'   => '中国银行',
            'ICBC'  => '工商银行',
            'CMB'   => '招商银行',
            'BOB'   => '北京银行',
            'CIB'   => '兴业银行',
            'PSBC'  => '邮储银行',
            'CITIC' => '中信银行',
            'ABC'   => '农业银行',
            'BEA'   => '东亚银行',
            'PAB'   => '平安银行',
            'CCB'   => '建设银行',
            'CMBC'  => '民生银行',
            'SPDB'  => '浦发银行',
            'GDB'   => '广发银行',
            'CEB'   => '光大银行',
            'BCM'   => '交通银行',
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

    /**
     * 微信、支付宝扫码网关
     * @var string
     */
    public $getwayUrl = 'https://gateway.xywallet.com/xywallet-api/order/ewalletOrderPrecreate';

    /**
     * 商户号，这里用的是appid
     * @var string
     */
    public $parterNo;

    /**
     * 商户key，这里用的是RSA私钥和公钥
     * @var string
     */
    public $parterKey;

    /**
     * 后端回调地址
     * @var string
     */
    public $callbackUrl;

    /**
     * 前端通知地址
     * @var string
     */
    public $notifyUrl;

    public $remark = 'CZ';

    public $service = 'xyw.order.precreate';

    public $signMethod = 'RSA';

    public $sign = '';

    public $charset = 'UTF-8';

    public $version = '1.0';

    public $format = 'json';

    /**
     * 随机数
     * @var string
     */
    public $canary;

    public $fee;

    public $currency = 'CNY';

    /**
     * 银行卡类型 01 借记卡
     * @var string
     */
    public $cardType = '01';

    public $orderNo = '';

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
        $reqChannel = '';

        if ($type == 12) {
            $reqChannel = 'WECHAT';
        }

        if ($type == 30) {
            $reqChannel = 'ALIPAY';
        }

        $this->fee     = $money;
        $this->remark  = $orderId;
        $this->orderNo = $orderId;

        $this->params = [
            'service'     => $this->service,
            'timestamp'   => date('Y-m-d H:i:s'),
            'charset'     => $this->charset,
            'v'           => $this->version,
            'format'      => $this->format,
            'canary'      => mt_rand(time(), time() + rand(10000, 99999)),
            'appId'       => $this->parterNo,
            'appUserName' => 'F79HCNDT6C9HY6XCGZYR',
            'payType'     => $type,
            'reqChannel'  => $reqChannel,
            'tradeAmt'    => $this->fee,
            'mchTradeNo'  => $this->orderNo,
            'subject'     => $this->remark,
            'body'        => $this->remark,
            'userNote'    => $this->remark,
            'notifyUrl'   => $this->callbackUrl,
        ];

        // 网银参数
        if (!in_array($type, ['12', '30'])) {
            unset($this->params['reqChannel']);
            $this->getwayUrl           = 'https://gateway.xywallet.com/xywallet-api/api'; // 网银网关
            $this->params['v']         = '1.1';
            $this->params['payType']   = '83';
            $this->params['service']   = 'xyw.ebank.apiprecreate';
            $this->params['currency']  = $this->currency;
            $this->params['cardType']  = $this->cardType;
            $this->params['returnUrl'] = $this->notifyUrl;
            $this->params['bankName']  = $this->payType[3][$type];
            $this->params['bankCode']  = $type;
            $this->params['tradeAmt']  = $money;
        }

        $rsaKey = json_decode($this->parterKey, true);
        $priKey = $rsaKey['priKey'];
        // $pubKey = $rsaKey['pubKey'];

        $plaintext = '';
        ksort($this->params);
        foreach ($this->params as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $plaintext .= $k . '=' . $v . '&';
        }
        $plaintext                  = substr($plaintext, 0, -1);
        $this->sign                 = Utils::rsaSign_string($plaintext, $priKey);
        $this->params['sign']       = $this->sign;
        $this->params['signMethod'] = $this->signMethod;

        return $this;
    }

    public function payment()
    {
        // echo '<pre>';
        // print_r($this->params);
        // echo '</pre>';
        $client = new Client();

        $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);

        if ($res->getStatusCode() == '200') {

            $resData = $res->getBody();

            if ($resData) {

                $returnArr = json_decode($resData, true);

                // echo '<pre>';
                // print_r($returnArr);
                // echo '</pre>';exit();

                // Array
                // (
                //     [retCode] => 00000
                //     [retMsg] => 操作成功
                //     [orderNo] => 20170320989248185704226458325635
                //     [appParams] =>
                //     [codeUrl] => weixin://wxpay/bizpayurl?pr=W9RO9Ed
                // )
                // Array
                // (
                //     [retCode] => 00000
                //     [retMsg] => 操作成功
                //     [orderNo] => 20170320426143689979793894020161
                //     [appParams] =>
                //     [codeUrl] => https://qr.alipay.com/bax02174bnltijlcymmg60f8
                // )

                if (isset($returnArr['retCode']) && $returnArr['retCode'] == '00000') {

                    $qrcode = $returnArr['codeUrl'];

                    $qrcodeUrl = Utils::getQrcode($qrcode);

                    return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . $this->fee . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);

                } else {

                    if (preg_match("/<.*<\/html>/", Utils::formatHtml($resData))) {
                        // 返回表单，自动POST提交
                        return $resData;
                    } elseif (Utils::isJSON($resData)) {
                        $tmpArr = json_decode($resData, true);
                        $tmpArr['order'] = $this->orderNo;
                        return json_encode($tmpArr);
                    } else {
                        // return $resData;
                        return json_encode(['order' => $this->orderNo]);
                    }

                }

            }

        }

    }

    public function verifySign($params = [])
    {
        $rsaKey = json_decode($this->parterKey, true);
        $pubKey = $rsaKey['pubKey'];
        if (Utils::validateRsaSign($pubKey, $params)) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {
    }
}
