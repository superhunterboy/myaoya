<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Af implements WMPay
{
    /**
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
            '10000103'=>'微信扫码'
        ],
        2  => [ //支付宝扫码
            '20000203'=>'支付宝扫码'
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
        ],
        5  => [ //京东扫码
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            '60000103'=>'银联钱包扫码'
        ],
        8  => [ //微信WAP
            '10000203'=>'微信WAP'
        ],
        9  => [ //支付宝WAP
            '20000203'=>'支付宝WAP'
        ],
        10 => [ //QQWAP
        ],
        11 => [ //京东WAP
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
    public $getwayUrl = 'https://gateway.aabill.com/cnpPay/initPay';

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
            $this->parterNo = $conf['parterNo'];

            $this->parterKey = $conf['parterKey']; // {"pubKey":"...","priKey":"..."}
            $parterarr=json_decode($this->parterKey,true);
            $this->paykey=$parterarr['paykey'];
            $this->paysecret=$parterarr['paysecret'];


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
        $this->money   = $money;

        $this->params['payKey']             =$this->paykey;
        $this->params['orderPrice']         =$this->money;
        $this->params['outTradeNo']         =$this->orderNo;
        $this->params['productType']        =$payType;
        $this->params['orderTime']          =date('YmdHis',time());
        $this->params['productName']        ='abc'.time().rand(10000,99999);
        $this->params['orderIp']            =Utils::getIp();
        $this->params['returnUrl']          =$this->callbackUrl;
        $this->params['notifyUrl']          =$this->callbackUrl;

        //$this->params['subPayKey']          ='def'.time().rand(10000,99999);
        //$this->params['remark']          ='def'.time().rand(10000,99999);

        $this->params['sign']=$this->getSignStr($this->params,$this->paysecret);
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params, 'verify' => false]);
        $body = $res->getBody();
        $resArr = json_decode($body, true);

        if ($this->params['productType'] == "20000203") {

            if($resArr['resultCode'] == '0000' && $resArr['returnMsg'] =="Success"){
                return $resArr['payMessage'];
            }
        }elseif ($this->params['productType'] == "60000103") {
            if ($resArr['resultCode'] == '0000' && $resArr['returnMsg'] == '成功') {
                $payQRCode = $resArr['resultCode'] ?? '';
                $payQRCodeUrl = $resArr['payMessage'] ?? '';
                $qrcodeUrl = Utils::getQrcode($payQRCodeUrl);
                return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
            }else {
                return json_encode(['order' => $this->orderNo]);
            }
        }else{
            if ($resArr['resultCode'] == '0000' && ($resArr['returnMsg'] == '请求成功')) {
                $payQRCode = $resArr['resultCode'] ?? '';
                $payQRCodeUrl = $resArr['payMessage'] ?? '';
                $qrcodeUrl = Utils::getQrcode($payQRCodeUrl);
                return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
                //return 'http://www.aoya.local/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
            } else {
                return json_encode(['order' => $this->orderNo]);
            }
        }
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];

        unset($params['sign']);
        $returnsign=$this->getSignStr($params,$this->paysecret);

        return $sign == $returnsign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($array = [], $paysecret = '')
    {
        ksort($array);
        $msg = "";
        // 转换为字符串 key=value&key.... 加签
        foreach ($array as $key => $val) {
            $msg = $msg . "$key=$val&";
        }
        $msg = $msg ."paySecret=".$paysecret;
        return strtoupper(md5($msg));
    }
}
