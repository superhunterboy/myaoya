<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Hebao implements WMPay
{
    public $payType = [
        1 => [
            'WEIXIN' => '微信扫码',
            // 'WEIXIN_H5' => '微信H5'
        ],
        2 => [
            'ZHIFUBAO' => '支付宝扫码',
        ],
        3 => [
            'ABC'     => '中国农业银行',
            'BOC'     => '中国银行',
            'BOCOM'   => '交通银行',
            'CCB'     => '中国建设银行',
            'ICBC'    => '中国工商银行',
            'PSBC'    => '中国邮政储蓄银行',
            'CMBC'    => '招商银行',
            'SPDB'    => '浦发银行',
            'CEBBANK' => '中国光大银行',
            'ECITIC'  => '中信银行',
            'PINGAN'  => '平安银行',
            'CMBCS'   => '中国民生银行',
            'HXB'     => '华夏银行',
            'CGB'     => '广发银行',
            'BCCB'    => '北京银行',
            'BOS'     => '上海银行',
            'BRCB'    => '北京农商银行',
            'CIB'     => '兴业银行',
            'SRCB'    => '上海农商银行',
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

    public $getwayUrl = 'http://api.hebaobill.com/gateway';

    public $key = '';

    public $input_charset   = 'UTF-8';
    public $notify_url      = '';
    public $return_url      = '';
    public $pay_type        = 1;
    public $bank_code       = '';
    public $merchant_code   = '';
    public $order_no        = '';
    public $order_amount    = 0;
    public $order_time      = '';
    public $product_name    = 'CZ';
    public $product_num     = 1;
    public $req_referer     = '';
    public $customer_ip     = '';
    public $customer_phone  = '';
    public $receive_address = '';
    public $return_params   = '';
    public $sign            = '';

    public $params            = [];
    public $tmp_merchant_code = [];
    public $tmp_key           = [];

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
            $this->merchant_code = $conf['parterNo'];
            $this->key           = $conf['parterKey'];
            $this->notify_url    = $conf['callbackUrl'];
            $this->return_url    = $conf['notifyUrl'];

            $this->tmp_merchant_code = json_decode($this->merchant_code, true);
            $this->tmp_key           = json_decode($this->key, true);
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
        // 区分微信、支付宝 商户号 和 key
        if ($type == 'WEIXIN') {
            $this->merchant_code = $this->tmp_merchant_code['wechat'];
            $this->key           = $this->tmp_key['wechat'];
        } elseif ($type == 'ZHIFUBAO') {
            $this->merchant_code = $this->tmp_merchant_code['alipay'];
            $this->key           = $this->tmp_key['alipay'];
        } else {
            $this->merchant_code = $this->tmp_merchant_code['netbank'];
            $this->key           = $this->tmp_key['netbank'];
        }

        $this->bank_code     = $type;
        $this->order_no      = $orderId;
        $this->order_amount  = $money;
        $this->order_time    = date('Y-m-d H:i:s');
        $this->return_params = $orderId;
        $this->product_name  = $orderId;

        $this->params = [
            'input_charset'   => $this->input_charset,
            'notify_url'      => $this->notify_url,
            'return_url'      => $this->return_url,
            'pay_type'        => $this->pay_type,
            'bank_code'       => $this->bank_code,
            'merchant_code'   => $this->merchant_code,
            'order_no'        => $this->order_no,
            'order_amount'    => $this->order_amount,
            'order_time'      => $this->order_time,
            'product_name'    => $this->product_name,
            'product_num'     => $this->product_num,
            'req_referer'     => $this->req_referer,
            'customer_ip'     => $this->customer_ip,
            // 'customer_phone'  => $this->customer_phone,
            'receive_address' => $this->receive_address,
            'return_params'   => $this->return_params,
            'sign'            => $this->sign,
        ];

        $signPars = Utils::getSignStr($this->params);

        $signPars .= "key=" . $this->key;

        $this->sign = md5($signPars);

        $this->params['sign'] = $this->sign;

        return $this;
    }

    public function payment()
    {
        $client = new Client();

        $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);

        if ($res->getStatusCode() == '200') {

            // <err>请求失败，原因：未开通该银行支付渠道权限</err>
            // <url>weixin://wxpay/bizpayurl?pr=adRMiY5</url>
            $returnXml = $res->getBody();

            file_put_contents(__DIR__ . '/../../../logs/qrcode-' . date('Ymd') . '.txt', date('Y-m-d H:i:s') . ' - ' . $returnXml . ' - ' . json_encode($this->params) . "\r\n", FILE_APPEND);

            if ($returnXml) {
                $url = '';
                $err = '';
                if (preg_match('/^<url>(.*)<\/url>$/', $returnXml)) {
                    $url = preg_replace('/^<url>(.*)<\/url>$/', '$1', $returnXml);
                }
                if (preg_match('/^<err>(.*)<\/err>$/', $returnXml)) {
                    $err = preg_replace('/^<err>(.*)<\/err>$/', '$1', $returnXml);
                }
                if ($err) {
                    return json_encode(['order' => $this->order_no, 'err' => $err]);
                } else {
                    if (preg_match("/weixin/", $url) || preg_match("/alipay/", $url)) {
                        $qrcodeUrl = Utils::getQrcode($url);
                        return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->order_no . '&fee=' . sprintf("%.2f", $this->order_amount) . '&qrcode=' . urlencode($qrcodeUrl);
                    } else {
                        return $url;
                    }
                }
            }

        }
    }

    public function verifySign($params = [])
    {
        $key = '';
        // payType 3 支付宝，2 微信，1 网银
        if ($params['payType'] == 3) {
            $key = $this->tmp_key['alipay'];
        } elseif ($params['payType'] == 2) {
            $key = $this->tmp_key['wechat'];
        } elseif ($params['payType'] == 1) {
            $key = $this->tmp_key['netbank'];
        }
        if (Utils::isZiyoufuSign($params['data'], $key)) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {
    }
}
