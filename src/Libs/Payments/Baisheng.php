<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Baisheng implements WMPay
{
    /**
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            'ALIPAY_QRCODE_PAY'=>'支付宝扫码'
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'QQ_QRCODE_PAY'=>'QQ扫码'
        ],
        5  => [ //京东扫码
          'JD_QRCODE_PAY'=>'京东扫码'
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            'UNIONPAY_QRCODE_PAY'=>'银联扫码'
        ],
        8  => [ //微信WAP
        ],
        9  => [ //支付宝WAP
            'ALIPAY_WAP_PAY'=>'支付宝WAP'
        ],
        10 => [ //QQWAP
        ],
        11 => [ //京东WAP
          'JD_WAP_PAY'=>'京东WAP'
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
            'UNIONPAY_WAP_PAY'=>'银联WAP'
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'https://ebank.baishengpay.com/Payment/Gateway';

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;

    /**
     * 商户key
     * @var string
     */
    public $parterKey;
    public $pubKey;
    public $priKey;
    public $payke;
    public $paysecret;

    /**
     * 回调地址
     * @var string
     */
    public $callbackUrl;

    /**
     * 通知地址
     * @var string
     */
    public $notifyUrl;

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
            $this->parterNo  = $conf['parterNo'];
            $this->parterKey = $conf['parterKey'];
            $this->callbackUrl = $conf['callbackUrl'];
            $this->notifyUrl   = $conf['notifyUrl'];
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
        $this->money   = $money * 100;

        $this->params['MerchantId']         =  $this->parterNo;
        $this->params['Timestamp']	        = date("Y-m-d H:i:s");
        $this->params['PaymentTypeCode']    = $payType;
        $this->params['OutPaymentNo']	    = $this->orderNo;
        $this->params['PaymentAmount']	    = $this->money;
        $this->params['NotifyUrl']	        = $this->callbackUrl;

        $this->params['Sign']               =  $this->getSignStr($this->params,$this->parterKey);
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if ($this->params['PaymentTypeCode'] == 'ALIPAY_WAP_PAY' || $this->params['PaymentTypeCode'] == 'JD_WAP_PAY' || $this->params['PaymentTypeCode'] == 'UNIONPAY_WAP_PAY'){
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
        }else{
            $client = new Client();
            $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params, 'verify' => false]);
            $body = $res->getBody();
            $resArr = json_decode($body, true);

            if($resArr['Code'] == 200){
                $payQRCodeUrl    = $resArr['Code'] ?? '';
                $payQRCode = $resArr['QrCodeUrl'] ?? '';
                $qrcodeUrl    = Utils::getQrcode($payQRCode);
                return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
            }else{
                return json_encode(['order' => $this->orderNo]);
            }
        }
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['Sign'];
        $strsign="Code=".$params['Code']."&MerchantId=".$params['MerchantId']."&OutPaymentNo=".$params['OutPaymentNo']."&PassbackParams=".$params['PassbackParams']."&PaymentAmount=".$params['PassbackParams']."&PaymentFee=".$params['PaymentFee']."&PaymentNo=".$params['PaymentNo']."&PaymentState=".$params['PaymentState'].$this->parterKey;

        $returnsign=MD5($strsign);
        return $sign == $returnsign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [], $paysecret = '')
    {
        ksort($params);
        $temp = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $temp .= $key . '=' . $value . '&';
            }
        }
        $temp=substr($temp,0,strlen($temp)-1);
        $md5str=$temp.$paysecret;
        return MD5($md5str);
    }
}
