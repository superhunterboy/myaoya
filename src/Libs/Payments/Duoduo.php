<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Duoduo implements WMPay
{
    public $paymentType = [
        1 => [
            'WECHATQR' => '微信',
        ],
        2 => [
            'ALIPAYQR' => '支付宝',
        ],
        3 => [
            'ABC'     => '农业银行',
            'ICBC'    => '工商银行',
            'CCB'     => '建设银行',
            'BCOM'    => '交通银行',
            'BOC'     => '中国银行',
            'CMB'     => '招商银行',
            'CMBC'    => '民生银行',
            'CEBB'    => '光大银行',
            'CIB'     => '兴业银行',
            'PSBC'    => '中国邮政',
            'SPABANK' => '平安银行',
            'ECITIC'  => '中信银行',
            'GDB'     => '广东发展银行',
            'HXB'     => '华夏银行',
            'SPDB'    => '浦发银行',
        ],
        4 => [
            'QQWALLET' => 'QQ钱包',
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
    public $getwayUrl = 'http://cashier.duoduopayment.com/payment/';

    public $merId;
    public $merKey;
    public $ordId;
    public $ordAmt;
    public $payType = 'DT';
    public $curCode = 'CNY';
    public $bankCode;
    public $productInfo;
    public $remark;
    public $returnURL;
    public $notifyURL;
    public $signType = 'MD5';
    public $signInfo;

    //初始化提交用数据数组
    public $params = [];

    //静态实例对象
    private static $instance;

    public static function getInstance($conf = [])
    {

        if (!(static::$instance instanceof static )) {

            static::$instance = new static($conf);

        }

        return static::$instance;
    }

    //对象初始化
    private function __construct($conf)
    {

        if ($conf) {

            $this->merId     = $conf['parterNo'];
            $this->merKey    = $conf['parterKey'];
            $this->returnURL = $conf['notifyUrl'];
            $this->notifyURL = $conf['callbackUrl'];
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

        $this->bankCode    = $payType;
        $this->ordId       = $orderId;
        $this->ordAmt      = $money;
        $this->productInfo = $orderId;
        $this->remark      = $orderId;

        $sign = "MerId={$this->merId}&OrdId={$this->ordId}&OrdAmt={$this->ordAmt}&PayType={$this->payType}&CurCode={$this->curCode}&BankCode={$this->bankCode}&ProductInfo={$this->productInfo}&Remark={$this->remark}&ReturnURL={$this->returnURL}&NotifyURL={$this->notifyURL}&SignType={$this->signType}&MerKey={$this->merKey}";

        $this->signInfo = md5($sign);

        $this->params = [
            'MerId'       => $this->merId,
            'OrdId'       => $this->ordId,
            'OrdAmt'      => $this->ordAmt,
            'PayType'     => $this->payType,
            'CurCode'     => $this->curCode,
            'BankCode'    => $this->bankCode,
            'ProductInfo' => $this->productInfo,
            'Remark'      => $this->remark,
            'ReturnURL'   => $this->returnURL,
            'NotifyURL'   => $this->notifyURL,
            'SignType'    => $this->signType,
            'SignInfo'    => $this->signInfo,
        ];

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
    public function verifySign($data = [])
    {
        if (Utils::verifySignDuoduo($data, $this->merKey)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询支付
     *$orderId    代付订单号
     **/

    public function query($orderId)
    {

        //时间参数
        $time = date('YmdHis');

        //电子签名
        $info = md5("{$this->merId}&{$time}&{$orderId}&{$this->merKey}");

        //组成提交数据
        $data = [
            'Version'   => '2.1',
            'SignType'  => $this->signType,
            'SignInfo'  => $info,
            'Charset'   => 'UTF-8',
            'TimeStamp' => $time,
            'MerId'     => $this->merId,
            'OrdId'     => $orderId,
        ];

        $tmp = '<html>';
        $tmp .= '<head>';
        $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
        $tmp .= '<title>Pay Page</title>';
        $tmp .= '</head>';
        $tmp .= '<body style="display:none;">';
        $tmp .= '<form action="' . $this->getwayUrl . '" method="post" name="orderForm">';
        foreach ($data as $key => $value) {
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

}
