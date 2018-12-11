<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Shanfu implements WMPay
{
    /**
     * 支付类型
     */
    public $payType = [
        1 => [
            '1004' => 'PC微信扫码',
            // '1007' => '手机微信扫码',
        ],
        2 => [
            '992' => 'PC支付宝扫码',
            // '1006' => '手机支付宝扫码',
        ],
        3 => [
            '962' => '中信银行',
            '963' => '中国银行',
            '964' => '中国农业银行',
            '965' => '中国建设银行',
            '967' => '中国工商银行',
            '970' => '招商银行',
            '971' => '邮政储蓄',
            '972' => '兴业银行',
            '976' => '上海农村商业银行',
            '977' => '浦东发展银行',
            // '978'  => '平安银行',
            '979' => '南京银行',
            '980' => '民生银行',
            '981' => '交通银行',
            '983' => '杭州银行',
            '985' => '广东发展银行',
            // '986'  => '光大银行',
            '987' => '东亚银行',
            '989' => '北京银行',
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
     * 支付网关地址
     */
    public $getwayUrl = 'https://gw.169.cc/interface/AutoBank/index.aspx';

    /**
     * 商户编号
     */
    public $parterNo;

    /**
     * 商户key
     */
    public $parterKey;

    /**
     * 备注
     */
    public $remark = 'CZ';

    /**
     * 回调地址
     */
    public $callbackUrl;

    /**
     * 通知地址
     */
    public $notifyUrl;

    /**
     * 查询地址
     */
    public $queryUrl = 'https://gw.169.cc/interface/search.aspx';

    /**
     * 签名uri
     */
    public $signUri;

    /**
     * 签名
     */
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

            $this->parterNo = $conf['parterNo'];

            $this->parterKey = $conf['parterKey'];

            // $this->remark        = $conf['remark'];

            $this->callbackUrl = $conf['callbackUrl'];

            $this->notifyUrl = $conf['notifyUrl'];
        }

    }

    private function __clone()
    {}

    /**
     * 获取支付类型
     */
    public function getPayType()
    {

        return $this->payType;

    }

    /**
     * 签名
     */
    public function signature($type, $money, $orderId)
    {

        $this->remark = $orderId;

        $this->signUri = sprintf("parter=%s&type=%s&value=%.2f&orderid=%s&callbackurl=%s", $this->parterNo, $type, $money, $orderId, $this->callbackUrl);

        $this->sign = md5($this->signUri . $this->parterKey);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {

        $url = $this->getwayUrl . "?{$this->signUri}&hrefbackurl={$this->notifyUrl}&attach={$this->remark}&sign={$this->sign}";

        // header("location: $url");

        // exit();

        return $url;
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {

        $sign    = $params['sign'];
        $orderid = $params['orderid'];
        $opstate = $params['opstate'];
        $ovalue  = $params['ovalue'];

        $userKey = $this->parterKey;

        $verifySign = md5("orderid=$orderid&opstate=$opstate&ovalue=$ovalue" . $userKey);

        if ($sign == $verifySign) {

            return true;

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
