<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//创优支付
class Ly implements WMPay
{
    /**  创优支付
     *   支付类型
     */


    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            "shunda001"=>"支付宝扫码"
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
            "shunda001"=>"支付宝WAP"
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
    public $getwayUrl = 'https://lingyunpay.alipayi.com/api/pay/getForm';
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

    private $pubKey = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCa9gDHO5nd07oJ5Y+MWhkU4Wht
nn6tP0cPb0khwdFFrnLB8Glr2Un2fhOqIbzPJ9vN9d0HfeorKHoMXmcu68QbmLJ1
BTMpxPqoQLacp4CCQwu81DSIUDfmqAzzoIiGAvfxxp+b7TOVb0OfElgzY3XHmT5a
GTEeH96eo0a/DkUKqwIDAQAB
-----END PUBLIC KEY-----';
    private $priKey='-----BEGIN RSA PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAJwNTtqYHPgMEjgs
Y15+rhkhHA+BeyFebsJAj58kGmZCtBUW9T0gE1sjM5indy86X2ZZzoE2Sx9MP8Pv
MKnMYUAuz6BJQ6eVPH4DFppjF2ycCnOxRLIo1ErIEiMuHIkpYdnu7RNPBcZIEw18
PxxjaRU0MmL2WC/6C/Y1NIgssnLVAgMBAAECgYALMjE8IbnCWitceGLQvsoC54s5
2ZJZ/Ip5yWQEiPST7ak58iG6NvjsiwQCqdE1RDzf/QWB48FUB4Zzqk+VfyJi+V7I
tOJJZGtqImlToeDL04Y5vrqMACNWjawL0UoYJ1fTq7a/AbxaEJf0YPsHFi8ypz6i
BUAI5lCy910QMYZoKQJBAO0viveALd2NWxk1cCPgUCtKCms2EGgue/g76fjCI+t4
fNG2nz7ueR5J/j/2DHVuomZxOBoLb0UAN2kD+7ZEe4sCQQCobjUiH+hrhrVFYFiR
gz2+R0Gx15pPjBJ8gOGyeOo6Gft3Bi0KWtLv9r2EZxy2bdN5S39djI3WK5SLgenb
qhcfAkEA3p172hb1dXeCJGPyj8mulSLWKpBAz/epJXDehjt5ST36MT8tsfxTs6kR
OGkwglvHk0z6sf8/fUzZtPgnHYijRwJAA277tk5gNpDwcRGdvJmoUJCzTiTPCIAa
gF9AOpssazLMZYjCsktXsXyeMGzJsQ5cGUTou8d5vLv2s0FauxekkQJBALx4CLWJ
g7TlBMnIP7aa9ocPR/JAGPt5HxzE5CPjw8Zr3+IJgOgcALvuhOyHhLTvjMR9xn5L
5bkFlqoXWYT0q8A=
-----END RSA PRIVATE KEY-----';




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
        $this->money   = $money;

        $this->params['memCode']                  =$this->parterNo;
        $this->params['channel']                  =$payType;
        $this->params['amount']                   =$this->money;
        $this->params['orderNo']                  =$this->orderNo;
        $this->params['notifyUrl']                =$this->callbackUrl;
        $this->params['returnUrl']                =$this->callbackUrl;
        $this->params['remark']                   ="abc".rand(10000,99999);

        $data="memCode={$this->parterNo}&channel={$payType}&amount={$this->money}&orderNo={$this->orderNo}&notifyUrl={$this->callbackUrl}";
        $signa=$this->getSign($data,$this->priKey);

        $this->params['sign']= $signa;

        return $this;
    }

    //生成rsa签名
    public function getSign($signString,$priKey)
    {
        $privKeyId = openssl_pkey_get_private($priKey);
        $signature = '';
        openssl_sign($signString, $signature, $privKeyId,OPENSSL_ALGO_MD5);
        openssl_free_key($privKeyId);
        return base64_encode($signature);
    }


    /*
     * 支付请求
     */
    public function payment()
    {

         $url=$this->getwayUrl;
/*
              $curl = curl_init();
              //设置抓取的url
              curl_setopt($curl, CURLOPT_URL, $url);
              //设置头文件的信息作为数据流输出
              curl_setopt($curl, CURLOPT_HEADER, 1);
              //设置获取的信息以文件流的形式返回，而不是直接输出。
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
              //设置post方式提交
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
             curl_setopt($curl, CURLOPT_POST, 1);
             curl_setopt($curl, CURLOPT_POSTFIELDS, $this->params);
            //执行命令
             $data = curl_exec($curl);
             $info = curl_getinfo($curl);
             //关闭URL请求
             curl_close($curl);
             //显示获得的数据
             echo "<pre>";
             print_r($info);
             print_r($data);
             echo "</pre>";

            die();
*/

         $client = new Client();
         $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params,'verify' => false]);
         $body = $res->getBody();
         $result=json_decode($body,true);

         if ($result['code'] == 1000000) {
           //echo $result['data'];die();
             $arr=explode("?",$result['data']);
             $arg=explode("=",$arr[1]);

             $tmp = '<html>';
             $tmp .= '<head>';
             $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
             $tmp .= '<title>Pay Page</title>';
             $tmp .= '</head>';
             $tmp .= '<body style="display:none;">';
             $tmp .= '<form action="' . $result['data'] . '" method="post" name="orderForm">';
             //$tmp .= '<input type="hidden" name="'.$arg[0].'" value="'.$arg[1].'">';

             $tmp .= '</form>';
             $tmp .= '<script type="text/javascript">';
             $tmp .= 'document.orderForm.submit();';
             $tmp .= '</script>';
             $tmp .= '</body>';
             $tmp .= '</html>';

             echo $tmp;
        }else{
          echo $result['msg'];
        }

    }
    /**
     * callback回调验证
     */

    function verifySign($param = []){
      $str="memCode={$param['memCode']}&orderNo={$param['orderNo']}&state={$param['state']}&amount={$param['amount']}";
      $sign = base64_decode($param['sign']);
      $key = openssl_pkey_get_public($this->pubKey);
      $result = openssl_verify($str, $sign, $key, OPENSSL_ALGO_MD5);
      return $result;
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
