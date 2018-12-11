<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;

class Xianxingfu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '1004' => '微信',
        ],
        2 => [
            '01003' => '支付宝',
        ],
        3 => [
            '962' => '中信银行',
            '963' => '中国银行',
            '964' => '中国农业银行',
            '965' => '中国建设银行',
            '966' => '中国工商银行（仅限工行手机签约客户）',
            '967' => '中国工商银行（全国范围）',
            '968' => '浙商银行',
            '969' => '浙江稠州商业银行',
            '970' => '招商银行',
            '971' => '邮政储蓄',
            '972' => '兴业银行',
            '973' => '顺德农村信用合作社',
            '974' => '深圳发展银行',
            '975' => '上海银行',
            '976' => '上海农村商业银行',
            '977' => '浦东发展银行',
            '978' => '平安银行',
            '979' => '南京银行',
            '980' => '民生银行',
            '981' => '交通银行',
            '982' => '华夏银行',
            '983' => '杭州银行',
            '984' => '广州市农村信用社|广州市商业银行',
            '985' => '广东发展银行',
            '986' => '光大银行',
            '987' => '东亚银行',
            '988' => '渤海银行',
            '989' => '北京银行',
            '990' => '北京农村商业银行',
        ],
        4 => [
            '01009' => 'QQ钱包',
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

    //993   财付通
    //1008  手机QQ钱包
    //1007  手机微信
    //1006  手机支付宝
    //1005  手机网银

    public $getwayUrl = 'http://pay.xianxingpay.com/bank'; //网关地址
    public $parter; //N Y 商户ID
    public $type; //N Y 银行类型
    public $value; //N Y 金额
    public $orderid; //N Y 商户订单号
    public $callbackurl; //N Y 下行异步通知地址
    public $hrefbackurl; //Y N 下行同步通知地址
    public $payerIp; //Y N 支付用户IP
    public $attach; //Y N 备注消息
    public $sign; //N - MD5签名
    public $parterKey; //商户key

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

            $this->parter = $conf['parterNo'];

            $this->parterKey = $conf['parterKey'];

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
        // 网银
        $this->type    = intval($payType);
        $this->value   = $money;
        $this->orderid = $orderId;
        $this->attach  = $orderId;

        $this->params = [
            'parter'      => $this->parter, //N Y 商户ID
            'type'        => $this->type, //N Y 银行类型
            'value'       => $this->value, //N Y 金额
            'orderid'     => $this->orderid, //N Y 商户订单号
            'callbackurl' => $this->callbackurl, //N Y 下行异步通知地址
            //'sign'            => $this->sign,
        ];

        $sign                        = md5(urldecode(http_build_query($this->params)) . $this->parterKey); //签名
        $this->params['hrefbackurl'] = $this->hrefbackurl;
        $this->params['attach']      = $this->attach;
        $this->params['sign']        = $sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {

        $tmp = '<html>';
        $tmp .= '<head>';
        $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
        $tmp .= '<title>Pay Page</title>';
        $tmp .= '</head>';
        $tmp .= '<body style="display:none;">';
        $tmp .= '<form action="' . $this->getwayUrl . '" method="get" name="orderForm">';
        foreach ($this->params as $key => $value) {
            $tmp .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        $tmp .= '</form>';
        $tmp .= '<script type="text/javascript">';
        $tmp .= 'document.orderForm.submit();';
        $tmp .= '</script>';
        $tmp .= '</body>';
        $tmp .= '</html>';

        return $tmp;
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = md5("orderid={$params['orderid']}&opstate={$params['opstate']}&ovalue={$params['ovalue']}{$this->parterKey}");
        if ($sign === $params['sign']) {
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
