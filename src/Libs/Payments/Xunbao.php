<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Xunbao implements WMPay
{
    /**
     * 支付类型
     */
    public $payType = [
        1 => [
            '8011' => '微信扫码支付',
        ],
        2 => [
            '8012' => '支付宝扫码支付',
        ],
        3 => [
            // '992' => '支付宝',
            '994'  => '快钱',
            '962'  => '中信银行',
            '963'  => '中国银行',
            '964'  => '中国农业银行',
            '965'  => '中国建设银行',
            '966'  => '中国工商银行（仅限工行手机签约客户）',
            '967'  => '中国工商银行（全国范围）',
            '968'  => '浙商银行',
            '969'  => '浙江稠州商业银行',
            '970'  => '招商银行',
            '971'  => '邮政储蓄',
            '972'  => '兴业银行',
            '973'  => '顺德农村信用合作社',
            '974'  => '深圳发展银行',
            '975'  => '上海银行',
            '976'  => '上海农村商业银行',
            '977'  => '浦东发展银行',
            '978'  => '平安银行',
            '979'  => '南京银行',
            '980'  => '民生银行',
            '981'  => '交通银行',
            '982'  => '华夏银行',
            '983'  => '杭州银行',
            '984'  => '广州市农村信用社|广州市商业银行',
            '985'  => '广东发展银行',
            '986'  => '光大银行',
            '987'  => '东亚银行',
            '988'  => '渤海银行',
            '989'  => '北京银行',
            '990'  => '北京农村商业银行',
            '2000' => '中信银行（快捷支付）',
            '2001' => '中国银行（快捷支付）',
            '2003' => '中国建设银行（快捷支付）',
            '2004' => '中国工商银行（快捷支付）',
            '2006' => '招商银行（快捷支付）',
            '2008' => '兴业银行（快捷支付）',
            '2012' => '浦发银行（快捷支付）',
            '2013' => '平安银行（快捷支付）',
            '2019' => '广州农村商业银行（快捷支付）',
            '2020' => '广东发展银行（快捷支付）',
            '2021' => '中国光大银行（快捷支付）',
            '2027' => '广州银行（快捷支付）',
        ],
        4 => [
            '993' => 'QQ钱包',
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
    public $getwayUrl = 'http://gateway.xunbaopay9.com/chargebank.aspx';

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
    public $queryUrl = 'http://gateway.xunbaopay9.com/Search.aspx';

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

        $sign       = $params['sign'];
        $orderid    = $params['orderid'];
        $opstate    = $params['opstate'];
        $ovalue     = $params['ovalue'];
        $systime    = $params['systime'];
        $sysorderid = $params['sysorderid'];

        $userKey = $this->parterKey;

        $verifySign = md5("orderid=$orderid&opstate=$opstate&ovalue=$ovalue&time=$systime&sysorderid=$sysorderid" . $userKey);

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
