<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Likefu implements WMPay
{
    public $payType = [
        1 => [
            'MSWEIXIN' => '微信扫码',
            // 'MSWEIXINWAP' => 'WAP微信',
            // 'MSWEIXINH5'  => '微信H5',
        ],
        2 => [
            'MSAli' => '支付宝扫码',
        ],
        3 => [
            'ICBC'       => '工商银行',
            'CMB'        => '招商银行',
            'ABC'        => '农业银行',
            'CCB'        => '建设银行',
            'BOC'        => '中国银行',
            'BOCO'       => '交通银行',
            'CIB'        => '兴业银行',
            'CMBC'       => '民生银行',
            'CEB'        => '光大银行',
            'PINGANBANK' => '平安银行',
            'GDB'        => '广发银行',
            'CTTIC'      => '中信银行',
            'PSBS'       => '中国邮政',
            'BCCB'       => '北京银行',
        ],
        4 => [
            'MSTENPAY' => '手机QQ',
        ],
        5 => [
            'MSJD' => '京东支付',
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

    public $getwayUrl = 'https://gateway.nowtopay.com/NowtoPay.html';

    public $partner;

    public $parterKey;

    public $callbackurl;

    public $hrefbackurl;

    public $banktype;
    public $paymoney;
    public $ordernumber;
    public $attach;
    public $isshow = 1;
    public $sign;

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

            $this->partner = $conf['parterNo'];

            $this->parterKey = $conf['parterKey'];

            // $this->remark        = $conf['remark'];

            $this->callbackurl = $conf['callbackUrl'];

            $this->hrefbackurl = $conf['notifyUrl'];

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
        $this->banktype    = $type;
        $this->paymoney    = $money;
        $this->ordernumber = $orderId;
        $this->attach      = $orderId;

        $signSource = sprintf("partner=%s&banktype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $this->partner, $this->banktype, $this->paymoney, $this->ordernumber, $this->callbackurl, $this->parterKey);

        $this->sign = md5($signSource);

        return $this;
    }

    public function payment()
    {
        $getUrl = $this->getwayUrl . "?banktype=" . $this->banktype;
        $getUrl .= "&partner=" . $this->partner;
        $getUrl .= "&paymoney=" . $this->paymoney;
        $getUrl .= "&ordernumber=" . $this->ordernumber;
        $getUrl .= "&callbackurl=" . $this->callbackurl;
        $getUrl .= "&hrefbackurl=" . $this->hrefbackurl;
        $getUrl .= "&attach=" . $this->attach;
        $getUrl .= "&sign=" . $this->sign;
        return $getUrl;
    }

    public function verifySign($params = [])
    {
        $signSource = sprintf("partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s", $params['partner'], $params['ordernumber'], $params['orderstatus'], $params['paymoney'], $this->parterKey);
        if ($params['sign'] == md5($signSource)) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {

    }
}
