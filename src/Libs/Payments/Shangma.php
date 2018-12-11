<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//28
class Shangma implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '001' => '微信',
        ],
        2 => [
            '002' => '支付宝',
        ],
        3 => [
            'BOC'    => '中国银行',
            'ABC'    => '农业银行',
            'ICBC'   => '工商银行',
            'CCB'    => '建设银行',
            'BOCO'   => '交通银行',
            'CMB'    => '招商银行',
            'CMBC'   => '民生银行',
            'CIB'    => '兴业银行',
            'SPDB'   => '浦发银行',
            'GDB'    => '广发银行',
            'CITIC'  => '中信银行',
            'CEB'    => '光大银行',
            'PSBC'   => '邮政银行',
            'PINGAN' => '平安银行',
            'BCCB'   => '北京银行',
            'HXB'    => '华夏银行',
            'SHB'    => '上海银行',
            'BJRCB'  => '北京农商银行',
            'NJB'    => '南京银行',
            'NBB'    => '宁波银行',
            'YL'     => '银联支付',
        ],
        4 => [
            '003' =>'QQ钱包',
        ],
        5 => [
            '004' => '京东钱包',
        ],
        6 => [
        ],
        7 => [
            '005' => '银联扫码',
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

    //支付网关地址
    public $getwayUrl = 'http://pay.shangmafu.com/merchantPay/webpay';
    //扫码网关地址
    public $qrcodeUrl = 'http://pay.shangmafu.com/merchantPay/scancodepay';

    //公共请求参数
    public $mchNo;          //商户编号
    public $parterKey;      //商户md5key
    public $pubKey;         //商户公钥
    public $priKey;         //商户私钥
    public $merKey;         //支付平台公钥
    public $notifyUrl;      //回调地址
    public $outTradeNo;     //商户系统内部订单号
    public $amount;         //金额，单位为分
    public $body;           //订单描述
    public $payDate;        //商户发起支付请求的日期, 格式:20170329121232
    public $channel='1';    //网银支付渠道(1.pc网关 2.移动h5 暂时只可用1);扫码支付渠道(1.微信 2.支付宝 3.qq钱包 4.京东扫码 5.银联扫码)
    public $sign;           //签名
    //网银请求参数
    public $returnUrl;      //通知地址
    public $bankCode;       //支付银行类型
    public $bankType='11';  //付款银行账户类型：11:借记卡 12:贷记卡
    //扫码请求参数
    public $title='surplus';//扫码订单标题
    //申明请求参数数组
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
            $keyArr = json_decode($conf['parterKey'], true);//{"pubKey":"...","priKey":"...","md5Key":"...","merKey":"..."}
            if (is_array($keyArr)) {
                $this->parterKey    = $keyArr['md5Key'];
                $this->pubKey       = $keyArr['pubKey'];
                $this->priKey       = $keyArr['priKey'];
                $this->merKey       = $keyArr['merKey'];
            }
            $this->mchNo     = $conf['parterNo'];
            $this->notifyUrl = $conf['callbackUrl'];
            $this->returnUrl = $conf['notifyUrl'];
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
        // 网银
        if (!in_array($payType, ['001','002','003','004','005'])) {
            $this->channel  = '1';
            $this->bankCode = $payType;
        } else {
            $this->channel = intval($payType);
        }
        $this->outTradeNo   = $orderId;
        $this->amount       = $money * 100;
        $this->body         = $orderId;
        $this->payDate      = date('YmdHis');

        $this->params = [
            'mchNo'         => $this->mchNo,          //商户编号
            'notifyUrl'     => $this->notifyUrl,      //回调地址
            'outTradeNo'    => $this->outTradeNo,     //商户系统内部订单号
            'amount'        => $this->amount,         //金额，单位为分
            'body'          => $this->body,           //订单描述
            'payDate'       => $this->payDate,        //商户发起支付请求的日期, 格式:20170329121232
            'channel'       => $this->channel,        //网银支付渠道(1.pc网关 2.移动h5 暂时只可用1);扫码支付渠道(1.微信 2.支付宝 3.qq钱包 4.京东扫码 5.银联扫码)
        ];
        if (!in_array($payType, ['001','002','003','004','005'])) {
            //网银请求参数
            $this->params['returnUrl']  = $this->returnUrl;      //通知地址
            $this->params['bankCode']   = $this->bankCode;       //支付银行类型
            $this->params['bankType']   = $this->bankType;       //付款银行账户类型：11:借记卡 12:贷记卡
        } else {
            //扫码请求参数
            $this->params['title']      = $this->title;          //扫码订单标题
        }
        //签名
        ksort($this->params);
        $md5Str = md5(urldecode(http_build_query($this->params)) . '&signKey=' . $this->parterKey);
        $this->sign             = Utils::rsaSign_string($md5Str, $this->priKey);
        $this->params['remark'] = 'smart';
        $this->params['sign']   = $this->sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if (empty($this->params['title'])) {
            //$sendWayUrl = $this->getwayUrl;
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
            //$sendWayUrl = $this->qrcodeUrl;
            $client = new Client();
            $res = $client->request('POST', $this->qrcodeUrl, [
                'verify'=>false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
                ],
                'form_params' => $this->params
            ]);
            if ($res->getStatusCode() == '200') {
                $returnData = $res->getBody();
                $result= mb_convert_encoding($returnData, "UTF-8", "GBK");
                $data = json_decode($result, true);
                $code = $data['resultCode'] ?? '';
                $msg = $data['resultMsg'] ?? '';
                if ($data && $code === '00') {
                    $state = $this->verifySign($data);
                    if ($state) {
                        //if ($code == '00') {
                            $qrcode = $data['qrcode'] ?? '';
                            $money  = $data['amount']/100 ?? '';
                            $qrcodeUrl = Utils::getQrcode($qrcode);
                            return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->outTradeNo . '&fee=' . sprintf("%.2f",$money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
                        //} else {
                        //    return json_encode(['code'=>$code, 'error'=>$msg], JSON_UNESCAPED_UNICODE);
                        //}
                    } else {
                        return json_encode(['code'=>'1', 'error'=>'签名错误!'], JSON_UNESCAPED_UNICODE);
                    }
                } else {
                    return json_encode(['code'=>$code, 'error'=>$msg], JSON_UNESCAPED_UNICODE);
                }
            } else {
                return json_encode(['code'=>$res->getStatusCode(), 'error'=>$res->getReasonPhrase()], JSON_UNESCAPED_UNICODE);
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
        ksort($params);
        $data = md5(urldecode(http_build_query($params)) . '&signKey=' . $this->parterKey);
//echo 'md5前:'. urldecode(http_build_query($params)) . '&signKey=' . $this->parterKey .'***md5后:'. md5(urldecode(http_build_query($params)) . '&signKey=' . $this->parterKey) .'***平台公钥:'.$this->merKey .'***返回sign:'.$sign;exit;
        if (Utils::rsaVerify_string($data, $this->merKey, $sign)) {
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
