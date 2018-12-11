<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class JinhaizheNew implements WMPay
{
    public $payType = [
        1 => [
            '6001' => '微信',
        ],
        2 => [
            '6003' => '支付宝',
        ],
        3 => [
            '1041000' => '中国银行',
            '1031000' => '中国农业银行',
            '1021000' => '中国工商银行',
            '1051000' => '中国建设银行',
            '3012900' => '交通银行',
            '3085840' => '招商银行',
            '3051000' => '中国民生银行',
            '3093910' => '兴业银行',
            '3102900' => '上海浦东发展银行',
            '3065810' => '广东发展银行',
            '3021000' => '中信银行',
            '3031000' => '光大银行',
            '4031000' => '中国邮政储蓄银行',
            '3071000' => '平安银行',
            '3131000' => '北京银行',
            '3133010' => '南京银行',
            '3133320' => '宁波银行',
            '3222900' => '上海农村商业银行',
            '5021000' => '东亚银行',
        ],
        4 => [
            '6011' => 'QQ扫码',
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
    public $getwayUrl = 'http://zf.szjhzxxkj.com/ownPay/pay';

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

    public $money;

    public $orderId;

    public $payTime;

    public $agencyCode = 0;

    public $cashier = 0;

    public $remark = 'CZ';

    public $payMethod;

    public $sign = '';

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
        $this->orderId   = $orderId;
        $this->money     = $money * 100;
        $this->remark    = $orderId;
        $this->payTime   = time();
        $this->payMethod = $type;

        $signStr = $this->parterNo . "|" . $this->orderId . "|" . $this->money . "|" . $this->notifyUrl . "|" . $this->callbackUrl . "|" . $this->payTime . "|" . $this->agencyCode . "|" . $this->remark . "|" . $this->remark . "|" . $this->remark;
        $rsaKey  = json_decode($this->parterKey, true);
        $priKey  = $rsaKey['priKey'];
        // $pubKey       = $rsaKey['pubKey'];
        $this->sign = Utils::rsaSign_string($signStr, $priKey);

        $this->params = [
            'merchantNo' => $this->parterNo,
            'requestNo'  => $this->orderId,
            'amount'     => $this->money,
            'payMethod'  => $this->payMethod,
            'pageUrl'    => $this->notifyUrl,
            'backUrl'    => $this->callbackUrl,
            'payDate'    => $this->payTime,
            'agencyCode' => $this->agencyCode,
            'cashier'    => $this->cashier,
            'remark1'    => $this->remark,
            'remark2'    => $this->remark,
            'remark3'    => $this->remark,
            'signature'  => $this->sign,
        ];

        if (!in_array($type, ['6001', '6003', '6011'])) {
            $this->payMethod                 = '6002';
            $this->params['payMethod']       = $this->payMethod;
            $this->params['cur']             = 'CNY';
            $this->params['bankType']        = $type;
            $this->params['bankAccountType'] = '11';
            $this->params['timeout']         = 60 * 24 * 7;
        }

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

            // $this->logger->addInfo('JinghaizheNew QRcode data：', json_decode($resData, true));
            file_put_contents(__DIR__ . '/../../../logs/qrcode-' . date('Ymd') . '.txt', date('Y-m-d H:i:s') . ' - ' . $resData . ' - ' . json_encode($this->params) . "\r\n", FILE_APPEND);

            if ($resData) {

                if ($this->verifyWechatAlipaySign($resData)) {

                    $returnArr = json_decode($resData, true);

                    // echo '<pre>';
                    // print_r($returnArr);
                    // echo '</pre>';exit();

                    // Array
                    // (
                    //     [backQrCodeUrl] => weixin://wxpay/bizpayurl?pr=X9JdZil
                    //     [backOrderId] => dcdacdb710ab49659b70e479561151d4
                    //     [sign] => DOPrUTC9XV0ryZETdbSShTpNyreb64QLzD/LnIyZCVleTcSheQbTaUxtFLumZkGcBfN9NC2OJtFR791mfX0Og4PIwsyUSYC81IBOH7GJq9DHlKU8v4NfZ0AZziqYdBAfuAHGUURKtVEV9qdLUANr1+TVUC4v9RHq4eZduerhku4=
                    // )

                    // Array
                    // (
                    //     [backQrCodeUrl] => https://qr.alipay.com/bax01200ez8veeff5qbb004a
                    //     [backOrderId] => b8c7be2ded37454e9e611feb5d43336f
                    //     [sign] => QQkJTUsxX08zjqnQHbmG2Sd/k7U1v6wBxoIjsRYfaKNOilVzjunQI+lATKniDLTnG9Bd5eaQckDz+Pz9UTkYx9faOdatRgTOjxrxyU/0f5xoGeJ6mts1sL2NpsC6eQQBvN3bXPydNBhpyV/RYge0TeeVzK4DCLH9vNgUE85j/R0=
                    // )

                    $qrcode = $returnArr['backQrCodeUrl'];

                    $qrcodeUrl = Utils::getQrcode($qrcode);

                    return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderId . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);

                } else {

                    $resData = Utils::formatHtml($resData);
                    if (preg_match("/<.*<\/html>/", $resData) || strpos($resData, '<form') !== false) {
                        // 返回表单，自动POST提交
                        return $resData;
                    } elseif (Utils::isJSON($resData)) {
                        $tmpArr          = json_decode($resData, true);
                        $tmpArr['order'] = $this->orderId;
                        return json_encode($tmpArr);
                    } else {
                        // return $resData;
                        return json_encode(['order' => $this->orderId]);
                    }

                }

            }

        }

    }

    public function verifySign($params = [])
    {
        $rsaKey = json_decode($this->parterKey, true);
        $pubKey = $rsaKey['pubKey'];
        if (Utils::verifyRsaSignForCallback($pubKey, $params)) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {
    }

    private function verifyWechatAlipaySign($jsonStr)
    {
        $rsaKey = json_decode($this->parterKey, true);
        $pubKey = $rsaKey['pubKey'];
        if (Utils::verifyRsaSign($pubKey, $jsonStr)) {
            return true;
        }
        return false;
    }

}
