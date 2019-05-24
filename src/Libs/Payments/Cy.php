<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//创优支付
class Cy implements WMPay
{
    /**  创优支付
     *   支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
            "62"=>"微信扫码"
        ],
        2  => [ //支付宝扫码
            "50"=>"支付宝扫码"
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
            "62"=>"微信WAP"
        ],
        9  => [ //支付宝WAP
            "51"=>"支付宝WAP"
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
    //public $getwayUrl = 'http://www.mygoodpay.cn/pay/api';
    public $getwayUrl = 'http://www.90xpay.cn/pay/api';
    //http://www.90xpay.cn/idx/api
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

        $this->params['amount']                  =$this->money;
        $this->params['out_trade_no']            =$this->orderNo;
        $this->params['notify_url']              =$this->callbackUrl;
        $this->params['back_url']                =$this->callbackUrl;
        $this->params['attach']                  ="abc".rand(10000,99999);
        $this->params['trade_type']              =$payType;
        $this->params['mch_id']                  =$this->parterNo;

        //$this->params['mch_create_ip']           =Utils::getIp();
        $this->params['mch_create_ip']           ='127.0.0.1';
        $this->params['body']                    ="abc".rand(10000,99999);

        $this->urlstr=$this->getSignStr($this->params,$this->parterKey);

        $this->params['sign']=md5($this->getSignStr($this->params,$this->parterKey));
        return $this;
    }

    /*
     * 支付请求
     */
    public function payment()
    {
         $url=$this->getwayUrl."?".$this->urlstr."&sign=".$this->params['sign'];
         $client = new Client();
         $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params,'verify' => false]);
         $body = $res->getBody();
         $result=json_decode($body,true);
         return $result['payUrl'];

/*       if($result['respCode'] == 00000){
             $tmp = '<html>';
             $tmp .= '<head>';
             $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
             $tmp .= '<title>Pay Page</title>';
             $tmp .= '</head>';
             $tmp .= '<body style="display:none;">';
             $tmp .= '<form action="' . $result['payUrl'] . '" method="post" name="orderForm">';
             $tmp .= '</form>';
             $tmp .= '<script type="text/javascript">';
             $tmp .= 'document.orderForm.submit();';
             $tmp .= '</script>';
             $tmp .= '</body>';
             $tmp .= '</html>';
             return $tmp;
         }
*

        /*
         if($result['respCode'] == 00000){
             //echo $result['respCode'];
             echo $result['payUrl'];
             header("Location:".$result['payUrl']);
         }*/
    }
    /**
     * callback回调验证
     */

    function verifySign($param = []){
        $args = array(
            'sign'        => $param['sign'],
            'out_trade_no'=> $param['out_trade_no'],
            'out_channel_no'=> $param['out_channel_no'],
        );

        $key=$this->parterKey;
        if(isset($args['sign'])) {
            $oldSign = $args['sign'];
            unset($args['sign']);
        } else {
            $oldSign = '';
        }

        ksort($args);
        $requestString = '';
        foreach($args as $k => $v) {
            $requestString .= $k . '='.($v);
            $requestString .= '&';
        }
        $requestString = substr($requestString,0,strlen($requestString)-1);
        $newSign = md5( $requestString."&key=".$key);
        return $param['sign'] == $newSign;
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
        $temp = '';
        foreach ($array as $key => $value) {
            if ($value != '') {
                $temp .= $key . '=' . $value . '&';
            }
        }

        return $temp."key=".$paysecret;
    }
}
