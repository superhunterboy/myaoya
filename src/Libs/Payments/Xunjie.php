<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Xunjie implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            '0122' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            '0103' => '支付宝扫码',
        ],
        3  => [ //网银
            'PSBC' => '邮储银行',
            'ABC'  => '农业银行',
            'ICBC' => '工商银行',
            'BOC'  => '中国银行',
            'CCB'  => '建设银行',
            'BOCM' => '交通银行',
            'CEBB' => '光大银行',
            'CMBC' => '民生银行',
            'GDB'  => '广发银行',
            'CMB'  => '招商银行',
            'BOS'  => '上海银行',
            'BOB'  => '北京银行',
        ],
        4  => [ //QQ扫码
            '0102' => 'QQ扫码',
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
    public $getwayUrl = 'http://pay88.cat39.com/trans/trans/api/back.json';

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
    public $pubKey;
    public $priKey;

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
            $this->parterNo = $conf['parterNo'];
            $keyArr         = json_decode($conf['parterKey'], true); // {"pubKey":"...","priKey":"..."}
            if (is_array($keyArr)) {
                $this->pubKey = $keyArr['pubKey'];
                $this->priKey = $keyArr['priKey'];
            }
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
        $this->money   = $money * 100; // 分
        if($payType=='0122')
        {
            $i = mt_rand(0,99);
            if($i<10)
            {
                $i = '0'.$i;
            }
            $this->money   = intval($money.$i); // 分
        }
        $this->params = [
            'version'         => '1.0.0',
            'transType'       => 'SALES',
            'merNo'           => $this->parterNo,
            'orderDate'       => date('Ymd'),
            'orderNo'         => $this->orderNo,
            'notifyUrl'       => $this->callbackUrl,
            'returnUrl'       => $this->notifyUrl,
            'transAmt'        => $this->money,
            'bankCode'        => $payType,
            'commodityName'   => 'YX', // 名称
            'commodityDetail' => 'CZ', // 描述
        ];

        if (in_array($payType, ['0122', '0102', '0103'])) {
            unset($this->params['returnUrl']);
            unset($this->params['bankCode']);
            $this->params['productId'] = $payType;
        } else {
            $this->params['productId'] = '0001';
        }
        if($payType=='0122')
        {
            $this->params['salesType'] = '0';
            $this->params['returnUrl'] = $this->notifyUrl;
        }
        $temp = $this->getSignStr($this->params);

        $this->params['signature'] = Utils::rsaSign_string($temp, $this->priKey);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
         //print_r($this->params);die;
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);
        if ($res->getStatusCode() == '200') {
            $resData = '';
            $body    = $res->getBody();
            while (!$body->eof()) {
                $resData .= $body->read(1024);
            }
            if ($resData) {
                if (preg_match("/<.*<\/html>/", Utils::formatHtml($resData))) {
                    // 返回表单，自动POST提交
                    return $resData;
                } elseif (Utils::isJSON($resData)) {
                    $resArr = json_decode($resData, true);
                    if ($this->verifySign($resArr) && isset($resArr['respCode']) && $resArr['respCode'] == 'P000') {
                        $payQRCode    = $resArr['payQRCode'] ?? '';
                        $payQRCodeUrl = $resArr['payQRCodeUrl'] ?? '';
                        $qrcodeUrl    = Utils::getQrcode($payQRCode);
                        return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
                    } else {
                        return json_encode(['order' => $this->orderNo]);
                    }
                } else {
                    return json_encode(['order' => $this->orderNo]);
                }
            }
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = '';
        if (isset($params['signature'])) {
            $sign = $params['signature'];
            unset($params['signature']);
        }
        $data = $this->getSignStr($params);
        return Utils::rsaVerify_string($data, $this->pubKey, $sign);
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        ksort($params);
        $temp = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $temp .= $key . '=' . $value . '&';
            }
        }
        return substr($temp, 0, strlen($temp) - 1);
    }
}
