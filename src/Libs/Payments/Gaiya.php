<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Gaiya implements WMPay
{
    /**
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
            'wxpay'=>'微信扫码'
        ],
        2  => [ //支付宝扫码
            'alipay'=>'支付宝扫码'
        ],
        3  => [ //网银
            '01020000' => '工商银行',
            '01050000' => '建设银行',
            '01030000' => '农业银行',
            '01000000' => '中国邮储银行',
            '03030000' => '光大银行',
            '03050000' => '民生银行',
            '04012900' => '上海银行',
            '04031000' => '北京银行',
            '03060000' => '广发银行',
        ],
        4  => [ //QQ扫码
            'qqpay'=>'qq扫码'
        ],
        5  => [ //京东扫码
            'jdpay'=>'jdpay'
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            'unionpayqr'=>'银联钱包扫码'
        ],
        8  => [ //微信WAP
        ],
        9  => [ //支付宝WAP
            'alipayh5'=>'支付宝h5'
        ],
        10 => [ //QQWAP
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
            'unionpayqrh5'=>'银联h5'
        ],
    ];

    /**
     * 支付网关地址
     * @var string
     */
    public $getwayUrl = 'http://api.gaia58.com/resolve/pay';

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
            $this->parterNo = $conf['parterNo'];
            $this->parterKey = $conf['parterKey']; // {"pubKey":"...","priKey":"..."}

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
        $this->money   = $money * 100; // 分

        $this->params['sendTime']      =date('YmdHis');
        $this->params['merchantId']    =$this->parterNo;
        $this->params['merOrderId']    =$this->orderNo;
        $this->params['txnAmt']        =$this->money;
        $this->params['frontUrl']      =$this->callbackUrl;
        $this->params['backUrl']       =$this->callbackUrl;
        $this->params['subject']       ='abc'.time().rand(10000,99999);
        $this->params['body']          ='def'.time().rand(10000,99999);

        if($payType =='wxpay' || $payType =='alipay' || $payType =='qqpay' || $payType =='jdpay' || $payType =='unionpayqr' || $payType =='unionpayqrh5' || $payType =='alipayh5'){
            $this->params['gateway']       =$payType;
            $this->params['sendIp']        =Utils::getIp();
        }else{
            $this->params['gateway']       ="bank";
            $this->params['bankId']        =$payType;
            $this->params['dcType']        ="01";
        }

        $this->params['signature']=$this->getSignStr($this->params,$this->parterKey);
        $this->params['subject']=base64_encode($this->params['subject']);
        $this->params['body']=base64_encode($this->params['body']);
        $this->params['signMethod']    ='MD5';
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if($this->params['gateway'] == 'alipay'){
            $client = new Client();
            $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params,'verify' => false]);

            $body    = $res->getBody();
            $resArr = json_decode($body, true);

            if (Utils::isJSON($body)) {
                $resArr = json_decode($body, true);
                if ($resArr['code'] == '1111' && $resArr['success'] == 1) {
                    $payQRCode    = $resArr['code'] ?? '';
                    $payQRCodeUrl = $resArr['payLink'] ?? '';
                    $qrcodeUrl    = Utils::getQrcode($payQRCodeUrl);

                    //return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
                    return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);

                } else {
                    return json_encode(['order' => $this->orderNo]);
                }
            } else {
                return json_encode(['order' => $this->orderNo]);
            }
        }else {
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
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['signature'];

        unset($params['signMethod']);
        unset($params['signaturn']);

        $returnsign=$this->getSignStr($params,$this->parterKey);

        return $sign == $returnsign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [], $payKey = '', $md5key = '')
    {
        ksort($array);
        $msg = "";
        $i = 0;
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            // 不参与签名
            if ($val != null && $key !== "signMethod" && $key !== "signature") {
                if ($i == 0) {
                    $msg = $msg . "$key=$val";
                } else {
                    $msg = $msg . "&$key=$val";
                }
                $i++;
            }
        }
        $msg = $msg . $payKey;
        return base64_encode(md5($msg));
    }
}
