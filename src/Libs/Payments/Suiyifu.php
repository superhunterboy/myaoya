<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Suiyifu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            '1004' => 'PC微信扫码',
        ],
        2  => [ //支付宝扫码
            '1006' => '支付宝扫码',
        ],
        3  => [ //网银
            '962'  => '中信银行',
            '963'  => '中国银行',
            '964'  => '中国农业银行',
            '965'  => '中国建设银行',
            '967'  => '中国工商银行',
            '970'  => '招商银行',
            '971'  => '中国邮政储蓄银行',
            '972'  => '兴业银行',
            '977'  => '浦东发展银行',
            '978'  => '平安银行',
            '980'  => '民生银行',
            '981'  => '交通银行',
            '982'  => '华夏银行',
            '985'  => '广东发展银行',
            '986'  => '光大银行',
            '1962' => '网银快捷',
        ],
        4  => [ //QQ扫码
            '993' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            '01002' => '京东扫码',
        ],
        6  => [ //百度扫码
            '001003' => '百度钱包',
        ],
        7  => [ //银联扫码
            '1001' => '银联扫码',
        ],
        8  => [ //微信WAP
            '1100' => 'H5微信',
        ],
        9  => [ //支付宝WAP
            '1101' => 'H5支付宝',
        ],
        10 => [ //QQWAP
            '1102' => 'H5QQ',
        ],
        11 => [ //京东WAP
            '01012' => 'H5京东',
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
    public $getwayUrl = 'https://gateway.easyipay.com/interface/AutoBank/index.aspx';

    public $parter;         #   商户ID
    public $type;           #   银行类型
    public $value;          #   金额(单位：元 小数点保留两位)
    public $orderid;        #   商户订单号
    public $callbackurl;    #   下行异步通知地址
    public $hrefbackurl;    #   下行同步通知地址(付款完毕后返回,可空):
    public $attach;         #   备注消息(可空)
    public $key;            #   商户秘钥

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
            $this->parter      = $conf['parterNo'];
            $this->key         = $conf['parterKey'];
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
        $this->orderNo = $orderId;
        $this->money   = $money * 100; // 分

        $this->params = [
            'parter'      => $this->parter,
            'type'        => intval($payType),
            'value'       => $money,
            'orderid'     => $orderId,
            'callbackurl' => $this->callbackurl,
            'hrefbackurl' => $this->hrefbackurl,
        ];

        $this->params['sign'] = $this->getSignStr($this->params);

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
        $tmp .= '<form action="' . $this->getwayUrl . '" method="post" name="orderForm">';
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
        $sign = $params['sign'];
        $mySign = $this->getSignStr($params);

        if ($sign === $mySign) {
            return ture;
        }
        return false;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    /**
     * getSignStr 签名生成
     */
    private function getSignStr($parter)
    {
        if (isset($parter['sign'])) {
            $sbOld = "orderid={$parter['orderid']}&opstate={$parter['opstate']}&ovalue={$parter['ovalue']}";
        } else {
            #进行签名处理，一定按照文档中标明的签名顺序进行
            $sbOld = "";
            #加入商户id
            $sbOld = $sbOld . "parter=" . $parter['parter'] . "&";
            #加入支付类型
            $sbOld = $sbOld . "type=" . $parter['type'] . "&";
            #加入订单金额
            $sbOld = $sbOld . "value=" . $parter['value'] . "&";
            #加入订单号
            $sbOld = $sbOld . "orderid=" . $parter['orderid'] . "&";
            #加入下行异步通知
            $sbOld = $sbOld . "callbackurl=" . $parter['callbackurl'];
        }
        
        return md5($sbOld . $this->key);
    }

}
