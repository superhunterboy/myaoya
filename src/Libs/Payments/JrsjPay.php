<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class JrsjPay implements WMPay
{
    /**
     * 支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            '21'=>'支付宝'
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
        ],
        8  => [ //微信WAP
        ],
        9  => [ //支付宝WAP
            '21'=>'支付宝WAP'
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
    public $getwayUrl = 'http://47.75.77.59/dgateway/ws/trans/nocard/makeOrder';

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
            $this->parterNo = $conf['parterNo'];            //商户号
            $parterarr = json_decode($conf['parterKey'],true);

            $this->paykey=$parterarr['paykey'];             //签名密钥
            $this->paysecret=$parterarr['paysecret'];       //加密密钥

            $this->callbackUrl = $conf['callbackUrl'];      //通知地址
            $this->notifyUrl   = $conf['notifyUrl'];        //通知地址
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

        $this->params['versionNo']               ="1";              // versionNo 接口版本号
        $this->params['mchNo']                   =$this->parterNo;      // mchNo 机构号
        $this->params['price']                   =$this->money;      // price 交易金额,
        $this->params['subject']                 ="subject".time();      // subject 商品名称
        $this->params['description']             ="description".time();      // description 订单描述
        $this->params['orderDate']               =date('YmdHis');      // orderDate 订单日期
        $this->params['tradeNo']                 =$this->orderNo;      // tradeNo 商户流水号
        $this->params['notifyUrl']               =$this->callbackUrl;      // notifyUrl 异步通知URL
        $this->params['callbackUrl']             =$this->callbackUrl;      // callbackUrl  页面回跳地址
        $this->params['payType']                 =$payType;      // payType 支付方式

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $plainReqPayload = json_encode($this->params, JSON_UNESCAPED_UNICODE);
        $plainText = urldecode($plainReqPayload);

        // 固定值
        $iv = '0102030405060708';
        $block = 16;
        $pad = $block - (strlen($plainText) % $block); //Compute how many characters need to pad
        $plainText .= str_repeat(chr($pad), $pad); // After pad, the str length must be equal to block or its integer multiples
        $encrypt_str =  openssl_encrypt($plainText,'aes-128-cbc', $this->paysecret, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);

        $data["mchNo"] = $this->parterNo;
        $data["payload"] = base64_encode($encrypt_str);
        $data["sign"] = strtoupper(md5($data["payload"].$this->paykey));

        $contextPath="";
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $this->getwayUrl . $contextPath );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_SSLVERSION, 1 );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
            'Content-type:application/json;charset=UTF-8'
        ));
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( $data, JSON_UNESCAPED_UNICODE ) );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $resp = curl_exec ( $ch );
        $result=json_decode($resp, true);
        if($result['state'] == "Successful"){
            $decoded = base64_decode($result['payload']);
            $decrypted = openssl_decrypt($decoded, 'AES-128-CBC', $this->paysecret, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
            $resultdecode=$this->pkcs5_unpad ( $decrypted );
            $isarray=json_decode($resultdecode,true);

            $successarr['state']=$result['state'];
            $successarr['payUrl']=$isarray['payUrl'];
            return json_encode($successarr);
        }else{
            $successarr['state']="NO";
            $successarr['payUrl']="";
        }



    }

    public function pkcs5_unpad($text)
    {
        $pad = ord($text {strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    }

    public function query($orderId)
    {
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];

        return $sign == strtoupper(md5($params['payload'].$this->paykey));
    }


    private function getSignStr($array = [], $paysecret = '')
    {

        $msg="amount=".$array['amount']."&notify_url=".$array['notify_url']."&partner=".$array['partner']."&pay_type=".$array['pay_type']."&request_time=".$array['request_time']."&trade_no=".$array['trade_no']."&";
        return md5(strtolower($msg).$this->parterKey);
    }
}
