<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Renxin implements WMPay
{
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
            // 'TENPAY'     => '财付通',
            // 'TENPAYWAP'  => 'WAP财付通',
        ],
        4 => [
            'QQ'         => 'QQ钱包',
        ],
        5 => [
            'JD'         => '京东钱包',
        ],
        6 => [
        ],
        7 => [
        ],
        8 => [//微信WAP
            'WEIXINWAP'  => 'WAP微信',
        ],
        9 => [//支付宝WAP
            'ALIPAYWAP'  => '支付宝WAP',
        ],
        10 => [//QQWAP
            'QQWAP'     => 'QQWAP',
        ],
        11 => [//京东WAP
            'JDWAP'      => '京东WAP',
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    public $getwayUrl = 'http://dpos.qqjun.cn/Online/GateWay'; //接口地址

    public $version   = '3.0'; //版本号

    public $method    = 'Rx.online.pay'; //接口名称

    public $partner; //商户id

    public $merKey; //商户key

    public $banktype; //银行类型

    public $paymoney; //金额

    public $ordernumber; //商户订单号

    public $callbackurl; //异步通知地址

    public $hrefbackurl; //同步通知地址

    public $goodsname; //商品名称

    public $attach; //备注信息

    public $isshow = '1'; //是否显示收银台

    public $sign; //MD5签名

    public $params = [];

    private static $instance;

    public static function getInstance($conf = [])
    {
        if (!(static::$instance instanceof static )) {
            static::$instance = new static($conf);
        }
        return static::$instance;
    }

    /**
     * 对象初始化
     */
    private function __construct($conf)
    {

        if ($conf) {
            $this->partner     = $conf['parterNo'];
            $this->merKey      = $conf['parterKey'];
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
        $money = sprintf("%.2f", $money);

        $this->banktype    = $payType;
        $this->ordernumber = $orderId;
        $this->paymoney    = $money;
        $this->goodsname   = $orderId;
        $this->attach      = $orderId;

        $sign = "version={$this->version}&method={$this->method}&partner={$this->partner}&banktype={$this->banktype}&paymoney={$this->paymoney}&ordernumber={$this->ordernumber}&callbackurl={$this->callbackurl}{$this->merKey}";

        $this->sign = md5($sign);

        $this->params = [
            'version'     => $this->version,
            'method'      => $this->method,
            'partner'     => $this->partner,
            'banktype'    => $this->banktype,
            'paymoney'    => $this->paymoney,
            'ordernumber' => $this->ordernumber,
            'callbackurl' => $this->callbackurl,
            'hrefbackurl' => $this->hrefbackurl,
            'goodsname'   => $this->goodsname,
            'attach'      => $this->attach,
            'isshow'      => $this->isshow,
            'sign'        => $this->sign,
        ];

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
    public function verifySign($data = [])
    {
        if (Utils::verifySignRenxin($data, $this->merKey)) {
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
