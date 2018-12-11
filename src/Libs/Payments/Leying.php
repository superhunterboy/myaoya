<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

// use GuzzleHttp\Client;

class Leying implements WMPay
{
    /**
     * 支付类型
     */
    public $payCode = [
        1 => [
            'wx' => '微信扫码',
        ],
        2 => [
            'zfb' => '支付宝',
        ],
        3 => [
            'icbc'   => '工商银行',
            'abc'    => '农业银行',
            'ccb'    => '建设银行',
            'boc'    => '中国银行',
            'comm'   => '交通银行',
            'cmb'    => '招商银行',
            'cmbc'   => '民生银行',
            'cib'    => '兴业银行',
            'spdb'   => '浦发银行',
            // 'hxb' => '华夏银行',
            'ecitic' => '中信银行',
            'ceb'    => '光大银行',
            'gdb'    => '广发银行',
            'post'   => '邮政储蓄',
            // 'sdb' => '深发展银行',
            'bea'    => '东亚银行',
            // 'nb' => '宁波银行',
            'bccb'   => '北京银行',
            'pingan' => '平安银行',
            // 'unionpay' => '银联综合接口64家银行'
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

    public $getwayUrl = 'https://www.funpay.com/website/pay.htm';

    public $parterKey; // 必须

    public $queryUrl = '';

    public $version = '1.0'; // 必须

    public $serialID; // 必须

    public $submitTime; // 必须

    public $failureTime; // 必须

    public $customerIP; // 可空

    public $orderDetails = "%s,%s,,CZ,1"; // 必须

    public $totalAmount; // 必须，金额

    public $type = '1000'; // 必填，1000即时支付

    public $buyerMarked; // 可空

    public $payType = 'ALL'; // 必填，ALL, BANK_B2C, WX, ACCT_RMB, BANK_B2B

    public $orgCode = ''; // 可空，仅在选择直联的状态下有效。需商务开通，如icbc

    public $currencyCode = '1'; // 可空，1人民币

    public $directFlag = '1'; // 必须，使用银企直连，0非直连，1直连

    public $borrowingMarked = '0'; // 可空，资金来源借贷标识，0无特殊要求，1只借记，2只贷记

    public $couponFlag = '1'; // 可空，1可用，0不可用

    public $platformID; // 可空

    public $returnUrl = ''; // 必须

    public $noticeUrl = ''; // 必须

    public $partnerID = ''; // 必须

    public $remark = 'CZ'; // 必须

    public $charset = '1'; // 必须，1为 utf-8

    public $signType = '2'; // 必须，1为RSA，2为MD5

    public $signMsg = ''; // 必须

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

            $this->partnerID = $conf['parterNo'];

            $this->parterKey = $conf['parterKey'];

            // $this->remark        = $conf['remark'];

            $this->returnUrl = $conf['callbackUrl'];

            $this->noticeUrl = $conf['notifyUrl'];
        }

    }

    private function __clone()
    {}

    /**
     * 获取支付类型
     */
    public function getPayType()
    {

        return $this->payCode;

    }

    /**
     * 签名
     */
    public function signature($type, $money, $orderId)
    {

        $this->remark = $orderId;

        if (!in_array($type, ['wx', 'zfb'])) {

            $this->payType = 'BANK_B2C';

            $this->orgCode = $type;

        } else {

            $this->payType = strtoupper($type);

            $this->orgCode = $type;

        }

        $this->submitTime = date('YmdHis');

        $this->failureTime = date('YmdHis', strtotime('+1year'));

        $this->serialID = "wm{$orderId}";

        $this->totalAmount = $money * 100; // sprintf("%.2f", $money);

        $this->orderDetails = sprintf($this->orderDetails, $orderId, $this->totalAmount);

        $this->signMsg = "version={$this->version}&";
        $this->signMsg .= "serialID={$this->serialID}&";
        $this->signMsg .= "submitTime={$this->submitTime}&";
        $this->signMsg .= "failureTime={$this->failureTime}&";
        $this->signMsg .= "customerIP={$this->customerIP}&";
        $this->signMsg .= "orderDetails={$this->orderDetails}&";
        $this->signMsg .= "totalAmount={$this->totalAmount}&";
        $this->signMsg .= "type={$this->type}&";
        $this->signMsg .= "buyerMarked={$this->buyerMarked}&";
        $this->signMsg .= "payType={$this->payType}&";
        $this->signMsg .= "orgCode={$this->orgCode}&";
        $this->signMsg .= "currencyCode={$this->currencyCode}&";
        $this->signMsg .= "directFlag={$this->directFlag}&";
        $this->signMsg .= "borrowingMarked={$this->borrowingMarked}&";
        $this->signMsg .= "couponFlag={$this->couponFlag}&";
        $this->signMsg .= "platformID={$this->platformID}&";
        $this->signMsg .= "returnUrl={$this->returnUrl}&";
        $this->signMsg .= "noticeUrl={$this->noticeUrl}&";
        $this->signMsg .= "partnerID={$this->partnerID}&";
        $this->signMsg .= "remark={$this->remark}&";
        $this->signMsg .= "charset={$this->charset}&";
        $this->signMsg .= "signType={$this->signType}";

        if ($this->charset == 1) {

            $charset = 'UTF8';

        }

        if ($this->signType == 2) {

            $this->signMsg = $this->signMsg . "&pkey={$this->parterKey}";

            // $this->signMsg = Utils::LAJPCall("com.funpay.gateway.client.php.ClientSignature::genSignByMD5", $this->signMsg, $charset);

            $this->signMsg = md5($this->signMsg);

        } elseif ($this->signType == 1) {

            $this->signMsg = Utils::LAJPCall("com.funpay.gateway.client.php.ClientSignature::genSignByRSA", $this->signMsg, $charset);

        }

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
        $tmp .= '<input type="hidden" name="version" value="' . $this->version . '">';
        $tmp .= '<input type="hidden" name="serialID" value="' . $this->serialID . '">';
        $tmp .= '<input type="hidden" name="submitTime" value="' . $this->submitTime . '">';
        $tmp .= '<input type="hidden" name="failureTime" value="' . $this->failureTime . '">';
        $tmp .= '<input type="hidden" name="customerIP" value="' . $this->customerIP . '">';
        $tmp .= '<input type="hidden" name="orderDetails" value="' . $this->orderDetails . '">';
        $tmp .= '<input type="hidden" name="totalAmount" value="' . $this->totalAmount . '">';
        $tmp .= '<input type="hidden" name="type" value="' . $this->type . '">';
        $tmp .= '<input type="hidden" name="buyerMarked" value="' . $this->buyerMarked . '">';
        $tmp .= '<input type="hidden" name="payType" value="' . $this->payType . '">';
        $tmp .= '<input type="hidden" name="orgCode" value="' . $this->orgCode . '">';
        $tmp .= '<input type="hidden" name="currencyCode" value="' . $this->currencyCode . '">';
        $tmp .= '<input type="hidden" name="directFlag" value="' . $this->directFlag . '">';
        $tmp .= '<input type="hidden" name="borrowingMarked" value="' . $this->borrowingMarked . '">';
        $tmp .= '<input type="hidden" name="couponFlag" value="' . $this->couponFlag . '">';
        $tmp .= '<input type="hidden" name="platformID" value="' . $this->platformID . '">';
        $tmp .= '<input type="hidden" name="returnUrl" value="' . $this->returnUrl . '">';
        $tmp .= '<input type="hidden" name="noticeUrl" value="' . $this->noticeUrl . '">';
        $tmp .= '<input type="hidden" name="partnerID" value="' . $this->partnerID . '">';
        $tmp .= '<input type="hidden" name="remark" value="' . $this->remark . '">';
        $tmp .= '<input type="hidden" name="charset" value="' . $this->charset . '">';
        $tmp .= '<input type="hidden" name="signType" value="' . $this->signType . '">';
        $tmp .= '<input type="hidden" name="signMsg" value="' . $this->signMsg . '">';
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

        $verifySign = '';

        $signMsg       = $params['signMsg'];
        $orderID       = $params['orderID'];
        $resultCode    = $params['resultCode'];
        $stateCode     = $params['stateCode'];
        $orderAmount   = $params['orderAmount'];
        $payAmount     = $params['payAmount'];
        $acquiringTime = $params['acquiringTime'];
        $completeTime  = $params['completeTime'];
        $orderNo       = $params['orderNo'];
        $partnerID     = $params['partnerID'];
        $remark        = $params['remark'];
        $charset       = $params['charset'];
        $signType      = $params['signType'];

        $userKey = $this->parterKey;

        // md5 验证方式，RSA后期待考虑
        if ($signType == 2) {

            $verifySign = md5("orderID=$orderID&resultCode=$resultCode&stateCode=$stateCode&orderAmount=$orderAmount&payAmount=$payAmount&acquiringTime=$acquiringTime&completeTime=$completeTime&orderNo=$orderNo&partnerID=$partnerID&remark=$remark&charset=$charset&signType=$signType&pkey=" . $userKey);

        } elseif ($signType == 1) {

            // RSA 方式

        }

        if ($signMsg == $verifySign) {

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
