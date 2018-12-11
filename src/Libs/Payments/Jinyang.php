<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//平台23
class Jinyang implements WMPay
{
    public $payType = [
        1 => [
            'WEIXIN' => '微信扫码',
        ],
        2 => [
            'ALIPAY' => '支付宝扫码',
        ],
        3 => [
            'ICBC'       => '工商银行',
            'ABC'        => '农业银行',
            'CCB'        => '建设银行',
            'BOC'        => '中国银行',
            'CMB'        => '招商银行',
            'BCCB'       => '北京银行',
            'BOCO'       => '交通银行',
            'CIB'        => '兴业银行',
            'NJCB'       => '南京银行',
            'CMBC'       => '民生银行',
            'CEB'        => '光大银行',
            'PINGANBANK' => '平安银行',
            'CBHB'       => '渤海银行',
            'HKBEA'      => '东亚银行',
            'NBCB'       => '宁波银行',
            'CTTIC'      => '中信银行',
            'GDB'        => '广发银行',
            'SHB'        => '上海银行',
            'SPDB'       => '浦发银行',
            'PSBS'       => '邮政银行',
            'HXB'        => '华夏银行',
            'BJRCB'      => '北京农商银行',
            'SRCB'       => '上海农商银行',
            'SDB'        => '深圳发展银行',
            'CZB'        => '浙江稠州商业银行',
        ],
        4 => [
            'QQPAY'     => 'QQ钱包',
        ],
        5 => [
            'JDPAY'     => '京东钱包',
        ],
        6 => [
            'BAIDUPAY'  => '百度钱包',
        ],
        7 => [
            'UNIONPAY'  => '银联扫码',
        ],
        8 => [//微信WAP
            'WEIXINWAP' => '微信WAP',
        ],
        9 => [//支付宝WAP
            'ALIPAYWAP' => '支付宝WAP',
        ],
        10 => [//QQWAP
            'QQPAYWAP'  => 'QQWAP',
        ],
        11 => [//京东WAP
            'JDPAYWAP'  => '京东WAP',
        ],
        12 => [//百度WAP
        ],
        13 => [//银联WAP
        ],
    ];

    public $getwayUrl = 'http://pay.095pay.com/zfapi/order/pay';

    public $p1_mchtid;              //商户ID,由金阳支付分配
    public $parterKey;              //商户key
    public $p2_paytype;             //支付方式
    public $p3_paymoney;            //支付金额  订单金额最小0.01(以元为单位）
    public $p4_orderno;             //商户平台唯一订单号
    public $p5_callbackurl;         //商户异步回调通知地址
    public $p6_notifyurl;           //商户同步通知地址
    public $p7_version = 'v2.8';    //版本号  V2.8
    public $p8_signtype = 1;        //签名加密方式 1.MD5
    //备注信息，上行中attach原样返回  p9_attach   否   String(128)     备注信息，上行中attach原样返回
    //分成标识    p10_appname 否   Strng(25)       分成标识
    public $p11_isshow = 0;         //是否显示PC收银台
    //商户的用户下单IP   p12_orderip 否   String(20)  192.168.10.1    商户的用户下单IP
    public $sign = '';              //签名 MD5签名

    public $params = [];

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
            $this->p1_mchtid        = $conf['parterNo'];
            $this->parterKey        = $conf['parterKey'];
            $this->p5_callbackurl   = $conf['callbackUrl'];
            $this->p6_notifyurl     = $conf['notifyUrl'];
        }

    }

    private function __clone()
    {}

    public function getPayType()
    {
        return $this->payType;
    }

    public function signature($type, $money, $orderId)
    {
        $this->p4_orderno       = $orderId;
        $this->p3_paymoney      = $money;
        $this->p2_paytype       = $type;

        $this->params = [
            'p1_mchtid'         => $this->p1_mchtid,              //商户ID,由金阳支付分配
            'p2_paytype'        => $this->p2_paytype,             //支付方式
            'p3_paymoney'       => $this->p3_paymoney,            //支付金额  订单金额最小0.01(以元为单位）
            'p4_orderno'        => $this->p4_orderno,             //商户平台唯一订单号
            'p5_callbackurl'    => $this->p5_callbackurl,         //商户异步回调通知地址
            'p6_notifyurl'      => $this->p6_notifyurl,           //商户同步通知地址
            'p7_version'        => $this->p7_version,             //版本号  V2.8
            'p8_signtype'       => $this->p8_signtype,            //签名加密方式 1.MD5
            'p9_attach'         => '',
            'p10_appname'       => '',
            'p11_isshow'        => $this->p11_isshow,             //是否显示PC收银台
            'p12_orderip'       => Utils::getIp(),
        ];
        //不签名字段
        //ksort($this->params);
        $signPars = urldecode(http_build_query($this->params)) . $this->parterKey;
        //$this->sign = md5($signPars);
        $this->params['sign'] = md5($signPars);

        return $this;
    }

    public function payment()
    {
        if (!in_array($this->params['p2_paytype'], ['WEIXIN','ALIPAY','QQPAY','JDPAY','BAIDUPAY','UNIONPAY'])) {
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
        } else {
            $returnJson = Utils::sendPost($this->getwayUrl, http_build_query($this->params));
            if (!$returnJson) {
                return json_encode(['code'=>1, 'error'=>'暂时不能使用'], JSON_UNESCAPED_UNICODE);
            }
            $data = json_decode($returnJson, true);
            $code = $data['rspCode'] ?? '';
            $msg  = $data['rspMsg'] ?? '';
            if (!empty($data) && $code == '1') {
                $state = $this->verifySign($data['data']);
                if ($state) {
                    $qrcode = $data['data']['r6_qrcode'] ?? '';
                    $money  = $data['data']['r4_amount'] ?? '';
                    $qrcodeUrl = Utils::getQrcode($qrcode);
                    return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->p4_orderno . '&fee=' . sprintf("%.2f", $this->p3_paymoney) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
                } else {
                    $result = ['code'=>1, 'error'=>'签名错误!'];
                }
            } else {
                $result = ['code'=>$code, 'error'=>$msg];
            }
            return json_encode($result, JSON_UNESCAPED_UNICODE);

            /*else{
                $client = new Client();                                                                                                               var_dump($this->getwayUrl.'====='.urldecode(http_build_query($this->params)));exit;
                $res = $client->request('POST', $this->getwayUrl, [
                    'verify'=>false,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
                    ],
                    'form_params' => $this->params,
                ]);
                if ($res->getStatusCode() == '200') {
                    $returnJson = $res->getBody();
                    if ($data = json_decode($returnJson, true)) {
                        $state = $this->verifySign($data['data'], $this->parterKey);
                        if ($state) {
                            $code = $data['rspCode'] ?? '';
                            $msg = $data['rspMsg'] ?? '';
                            if ($code == 1) {
                                $qrcode = $data['data']['r6_qrcode'] ?? '';
                                $money = $data['data']['r4_amount'] ?? '';
                                return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->p4_orderno . '&fee=' . sprintf("%.2f",$money) . '&qrcode=' . urlencode($qrcode);
                            } else {
                                if ($msg) {
                                    $msg = json_decode(ltrim($msg, 'Error:'), true);
                                    $result =['status'=>1, 'msg'=>$msg['rt3_retMsg']];
                                }else{
                                    $result = ['status'=>1, 'msg'=>'暂时不能使用'];
                                }
                                return json_encode($result, JSON_UNESCAPED_UNICODE);
                            }
                        }
                    }
                }*/
            return false;
        }
    }

    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        if (isset($params['attach'])) {
            unset($params['attach']);
        }
        if (isset($params['sysnumber'])) {
            unset($params['sysnumber']);
        }
        //ksort($params);
        $signStr = urldecode(http_build_query($params)) . $this->parterKey;
        $mySign = md5($signStr);

        if ($sign == $mySign) {
            return true;
        }
        return false;
    }

    public function query($orderId)
    {

    }
}