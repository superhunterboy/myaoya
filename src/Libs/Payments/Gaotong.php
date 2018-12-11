<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Gaotong implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            'WEIXIN' => '微信',
        ],
        2 => [
            'ALIPAY' => '支付宝',
        ],
        3 => [
            'ICBC'       => '工商银行',
            'ABC'        => '农业银行',
            'CCB'        => '建设银行',
            'BOC'        => '中国银行',
            'CMB'        => '招商银行',
            'BCCB'       => '北京银行',
            'BOCO'       => '交通银行',
            'CIB'        => '兴业银行',
            'NJCB'       => '南京银行',
            'CMBC'       => '民生银行',
            'CEB'        => '光大银行',
            'PINGANBANK' => '平安银行',
            'CBHB'       => '渤海银行',
            'HKBEA'      => '东亚银行',
            'NBCB'       => '宁波银行',
            'CTTIC'      => '中信银行',
            'GDB'        => '广发银行',
            'SHB'        => '上海银行',
            'SPDB'       => '上海浦东发展银行',
            'PSBS'       => '中国邮政',
            'HXB'        => '华夏银行',
            'BJRCB'      => '北京农村商业银行',
            'SRCB'       => '上海农商银行',
            'SDB'        => '深圳发展银行',
            'CZB'        => '浙江稠州商业银行',
            'SRCB'       => '上海农商行',
            // 'TENPAY'     => '财付通',
        ],
        4 => [
            'QQPAY' => 'QQ钱包',
        ],
        5 => [
            'JDPAY' => '京东扫码',
        ],
        6 => [
            'BAIDUPAY' => '百度钱包',
        ],
        7 => [
            'UNIONPAY' => '银联扫码',
        ],
        8 => [//微信WAP
            'WEIXINWAP'  => '手机微信WAP',
        ],
        9 => [//支付宝WAP
            'ALIPAYWAP'  => '手机支付宝WAP',
        ],
        10 => [//QQWAP
            'QQPAYWAP'   => '手机QQ钱包WAP',
        ],
        11 => [//京东WAP
            'JDPAYWAP' => '京东钱包WAP',
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://wgtj.gaotongpay.com/PayBank.aspx'; // 'http://wgtj.gaotongpay.com/PayBank.aspx';

    /**
     * 商户编号
     * @var string
     */
    public $partner;

    /**
     * 商户key
     * @var string
     */
    public $parterKey;

    /**
     * 回调地址
     * @var string
     */
    public $callbackurl;

    public $hrefbackurl;

    /**
     * 通知地址
     * @var string
     */
    public $notifyUrl;

    public $banktype;

    public $paymoney;

    public $ordernumber;

    public $attach;

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
            $this->partner     = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
            $this->callbackurl = $conf['callbackUrl'];
            $this->hrefbackurl = $conf['notifyUrl'];
        }
    }

    private function __clone()
    {
    }

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
        $this->params['partner']     = $this->partner;
        $this->params['banktype']    = $payType;
        $this->params['paymoney']    = $money;
        $this->params['ordernumber'] = $orderId;
        $this->params['callbackurl'] = $this->callbackurl;
        $this->params['hrefbackurl'] = $this->hrefbackurl;
        $this->params['attach']      = $orderId;
        $this->params['sign']        = Utils::gaotongSign($this->params, $this->parterKey);
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        return $this->getwayUrl . '?' . http_build_query($this->params);
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign_str = 'partner=' . $params['partner'] . '&ordernumber=' . $params['ordernumber'] . '&orderstatus=' . $params['orderstatus'] . '&paymoney=' . $params['paymoney'] . $this->parterKey;
        if (md5($sign_str) == $params['sign']) {
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
