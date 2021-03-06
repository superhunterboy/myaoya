<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Haitu implements WMPay
{
    /**  海图
     *   支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
            "pay.weixin.scan.trade.precreate"=>"微信扫码"
        ],
        2  => [ //支付宝扫码
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
            "pay.weixin.scan.trade.precreate "=>"微信WAP"
        ],
        9  => [ //支付宝WAP
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
    public $getwayUrl = 'http://kpay.jk2jd.com/pay/gateway';

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
        $this->money   = $money*100;

        $this->params['service']            =$payType;
        $this->params['mch_id']             =$this->parterNo;
        $this->params['nonce_str']          =$this->getRandomString(20);
        $this->params['out_trade_no']       =$this->orderNo;
        $this->params['body']               ='支付金额:'.$this->money;
        $this->params['total_fee']          =$this->money;
        $this->params['spbill_create_ip']   ='127.0.0.1';
        $this->params['notify_url']         =$this->callbackUrl;

        $this->params['sign']=strtoupper($this->getSignStr($this->params,$this->parterKey));

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $o = "";
        foreach ( $this->params as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$this->getwayUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);//运行curl
        $result=json_decode($data, true);
        curl_close($ch);

        if($result['code'] == 0){
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $result['qr_code'] . '" method="post" name="orderForm">';
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
    public function verifySign($data = [])
    {

        $sign = $data['sign'];
        unset($data['sign']);
        ksort ($data);
        $str = "";
        foreach ($data as $k => $v){
            if (!isset($v) || is_null($v) || empty($v)) {
                unset($data[$k]);
                continue;
            } else {
                $str .= $k . "=" . $v . "&";
            }
        }
        $str .= "key=" . $this->parterKey;
        return strtoupper(md5($str)) == $sign;

    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($data = [], $key = '')
    {
        ksort ($data);
        $str = "";
        foreach ($data as $k => $v){
            if (!isset($v) || is_null($v) || empty($v)) {
                unset($data[$k]);
                continue;
            } else {
                $str .= $k . "=" . $v . "&";
            }
        }
        $str .= "key=" . $key;
        return md5($str);
    }

    private function getRandomString($len, $chars=null)  
    {  
        if (is_null($chars)) {  
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
        }  
        mt_srand(10000000*(double)microtime());  
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {  
            $str .= $chars[mt_rand(0, $lc)];  
        }  
        return $str;  
    }
}
