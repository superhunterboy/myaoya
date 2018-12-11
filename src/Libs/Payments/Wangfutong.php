<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//25
class Wangfutong implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '1004'  => '微信',
        ],
        2 => [
            '992'   => '支付宝',
        ],
        3 => [
            '962'   => '中信银行',
            '963'   => '中国银行',
            '964'   => '中国农业银行',
            '965'   => '中国建设银行',
            '966'   => '中国工商银行(仅限工行手机签约客户)',
            '967'   => '中国工商银行(全国范围)',
            '968'   => '浙商银行',
            '969'   => '浙江稠州商业银行',
            '970'   => '招商银行',
            '971'   => '邮政储蓄',
            '972'   => '兴业银行',
            '973'   => '顺德农村信用合作社',
            '974'   => '深圳发展银行',
            '975'   => '上海银行',
            '976'   => '上海农村商业银行',
            '977'   => '浦东发展银行',
            '978'   => '平安银行',
            '979'   => '南京银行',
            '980'   => '民生银行',
            '981'   => '交通银行',
            '982'   => '华夏银行',
            '983'   => '杭州银行',
            '984'   => '广州市农村信用社|广州市商业银行',
            '985'   => '广东发展银行',
            '986'   => '光大银行',
            '987'   => '东亚银行',
            '988'   => '渤海银行',
            '989'   => '北京银行',
        ],
        4 => [
            '1010'  => 'QQ钱包',
        ],
        5 => [
        ],
        6 => [
        ],
        7 => [
        ],
        8 => [//微信WAP
            '01007' => '微信WAP',
        ],
        9 => [//支付宝WAP
            '01006' => '支付宝WAP',
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

    //支付网关
    public $getwayUrl = 'http://vip.qianhaiepay.com/bank/'; //http://vip.paywft.com/bank

    public $parter;      //N Y 商户 id
    public $parterKey;   //N Y 商户key
    public $type;        //N Y 银行类型
    public $value;       //N Y 单位元（人民币），2 位小数，最小支付金额为 0.02
    public $orderid;     //N Y 商户系统订单号
    public $callbackurl; //N Y 下行异步通知过程的返回地址
    public $hrefbackurl; //Y N 下行同步通知过程的返回地址
    public $sign;        //N - 32 位小写 MD5 签名值，GB2312 编码

    //提交参数
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
            $this->parter           = $conf['parterNo'];
            $this->parterKey        = $conf['parterKey'];
            $this->callbackurl      = $conf['callbackUrl'];
            $this->hrefbackurl      = $conf['notifyUrl'];
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
        //$money            = sprintf("%.2f", $money);
        $this->value      = $money;
        $this->orderid    = $orderId;
        $this->type       = intval($payType);

        $this->params     = [
            'parter'      => $this->parter,      //N Y 商户 id
            'type'        => $this->type,        //N Y 银行类型
            'value'       => $this->value,       //N Y 单位元（人民币），2 位小数，最小支付金额为 0.02
            'orderid'     => $this->orderid,     //N Y 商户系统订单号
            'callbackurl' => $this->callbackurl, //N Y 下行异步通知过程的返回地址
        ];
        //签名
        $signStr = urldecode(http_build_query($this->params)) . $this->parterKey;
        $this->sign = md5(iconv('utf-8', 'gb2312', $signStr));
        $this->params['hrefbackurl']     = $this->hrefbackurl;
        $this->params['sign'] = $this->sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        return $this->getwayUrl .'?'. http_build_query($this->params);
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign    = $params['sign'] ?? '';
        $orderid = $params['orderid'] ?? '';
        $opstate = $params['opstate'] ?? '';
        $ovalue  = $params['ovalue'] ?? '';

        $signStr = "orderid={$orderid}&opstate={$opstate}&ovalue={$ovalue}" . $this->parterKey;
        $mySign  = md5($signStr);

        if ($sign == $mySign) {
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