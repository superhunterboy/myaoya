<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Huida implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            'WXPAY' => '微信',
        ],
        2 => [
            'ALIPAY' => '支付宝',
        ],
        3 => [
            'CMB'    => '招商银行',
            'ICBC'   => '工商银行',
            'CCB'    => '建设银行',
            'BOC'    => '中国银行',
            'ABC'    => '农业银行',
            'BOCM'   => '交通银行',
            'SPDB'   => '浦发银行',
            'CGB'    => '广发银行',
            'CITIC'  => '中信银行',
            'CEB'    => '光大银行',
            'CIB'    => '兴业银行',
            'PAYH'   => '平安银行',
            'CMBC'   => '民生银行',
            'HXB'    => '华夏银行',
            'PSBC'   => '邮储银行',
            'BCCB'   => '北京银行',
            'SHBANK' => '上海银行',
        ],
        4 => [
            'QQPAY' => 'QQ钱包',
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

    //支付网关
    public $getwayUrl = 'https://ebank.huidpay.com';

    //公用参数
    public $body;   //    String（50）  Y   商品的具体描述（Product Description）
    public $buyerEmail; //  String  N   买家 Email（Buyer email address）
    public $charset = 'UTF-8';    // String  Y   参数编码字符集
    public $defaultbank;    // String  N   网银代码，当支付方式为bankPay时，该值为空；支付方式为directPay时该值必传，值见银行列表
    public $isApp = 'web';  //   String  Y   接入方式，当该值传“app”时，表示app接入，返回二维码地址，需商户自行生成二维码；值为“web”时，表示web接入，直接在收银台页面上显示二维码；值为“H5”时，表示手机端html5接入，会在手机端唤醒支付app
    public $merchantId; //  String  Y   支付平台分配的商户ID（Merchant ID distributed by payment platform）
    public $notifyUrl;  //   String（128） Y   商户支付成功后，该地址将收到支付成功的异步通知信息，该地址收到的异步通知作为发货依据
    public $orderNo;    // String(64)  Y   商户订单号，务必确保在系统中唯一（Merchant order number, must uniqueness）
    public $paymentType_ = 1;    // String(4)   Y   支付类型，固定值为1（Payment type, fixed value is 1）
    public $paymethod = 'directPay';  //   String  Y   支付方式，directPay：直连模式；bankPay：收银台模式
    public $returnUrl;  //   String（128） Y   支付成功跳转URL，仅适用于支付成功后立即返回商户界面。我司处理完请求后，将立即跳转并把处理结果返回给这个URL
    public $riskItem;   //    String  N   风控字段，默认为空（The risk parameter, default is empty）
    public $sellerEmail;    // String  Y   卖家 Email，开通商户号时填写的邮箱账号（Seller email address, system will show you when the merchant opens）
    public $service = 'online_pay';    // String  Y   固定值online_pay，表示网上支付（Fixed value: online_pay indicates）
    public $title;  //   String（50）  Y   商品的名称，请勿包含字符（Product Name, do not include characters）
    public $totalFee;   //    Number(13,2)    Y   订单金额，单位为RMB元（Order Amount, unit is RMB）
    public $parterKey;  //商户key
    public $codeNum;  //扫码商户号
    public $bankNum;  //银行商户号
    public $codeKey;  //扫码key
    public $bankKey;  //银行key
    public $merchKey;  //实际参与签名的key
    public $signType = 'SHA';  //加密方式
    public $sign;  //加密数据

    /**
     * 提交参数
     * @var array
     */
    public $params = [];


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
            $this->merchantId        = $conf['parterNo']; //{"code":100000000002304,"bank":100000000002303}
            if($merchanArr = json_decode($this->merchantId, true)){
                $this->codeNum = $merchanArr['code'];
                $this->bankNum = $merchanArr['bank'];
            }
            $this->parterKey            = $conf['parterKey']; //{"code":"22fb3b325bgd8b6af2cf45cf8db22aec564b68gd1d07ba17b1fd0b747968g73a","bank":"0badaffe6f7ee66a39d347c64eda4e96a99c9ae8ae4f18b37c840dbeffe3ag11"}
            if($merchanArr = json_decode($this->parterKey, true)){
                $this->codeKey = $merchanArr['code'];
                $this->bankKey = $merchanArr['bank'];
            }
            $this->notifyUrl           = $conf['callbackUrl'];
            $this->returnUrl           = $conf['notifyUrl'];
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
        $money                  = sprintf("%.2f", $money);
        $this->totalFee         = $money;
        $this->orderNo          = $orderId;
        $this->title            = $orderId;
        $this->body             = $orderId;
        $this->defaultbank      = $payType;
        if (!in_array($payType, ['WXPAY', 'ALIPAY', 'QQPAY'])) {
            // 网银
            $this->params['merchantId']   = $this->bankNum;
            $this->merchKey               = $this->bankKey;
            $this->sellerEmail            = 'huidawangyin@gmail.ocm';
        }else{
            $this->params['merchantId']   = $this->codeNum;
            $this->merchKey               = $this->codeKey;
            $this->sellerEmail            = 'huidaerwei@gmail.com';
        }
        // 参数合成
        $this->params['body']           = $this->body;
        $this->params['charset']        = $this->charset;
        $this->params['defaultbank']    = $this->defaultbank;
        $this->params['isApp']          = $this->isApp;
        $this->params['notifyUrl']      = $this->notifyUrl;
        $this->params['orderNo']        = $this->orderNo;
        $this->params['paymentType']    = $this->paymentType_;
        $this->params['paymethod']      = $this->paymethod;
        $this->params['returnUrl']      = $this->returnUrl;
        $this->params['sellerEmail']    = $this->sellerEmail;
        $this->params['service']        = $this->service;
        $this->params['title']          = $this->title;
        $this->params['totalFee']       = $this->totalFee;

        //排序&签名
        $bool =ksort($this->params);
        $this->sign = strtoupper(sha1(urldecode(http_build_query($this->params)).$this->merchKey));
        $this->params['sign'] = $this->sign;
        $this->params['signType'] = $this->signType;

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
        $tmp .= '<form action="' . $this->getwayUrl . '/payment/v1/order/'.$this->params['merchantId']. '-' .$this->params['orderNo'] . '" method="post" name="orderForm">';
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

        // $client = new Client(['base_uri' => $this->getwayUrl, 'timeout' => 10.0]);
        // $res    = $client->request('POST', '/payment/v1/order/'.$this->params['merchantId']. '-' .$this->params['orderNo'],
        //     [
        //         'verify' => false,
        //         'Content-Type' => 'text/xml; charset=UTF-8',
        //         'query' => $this->params
        //   ]);
        // if ($res->getStatusCode() == '200') {
        //     $resData = $res->getBody();
        //     if(preg_match("/.*?<\/html>/", $resData)){
        //         $data = preg_replace('/src=\"\/(.*)\">/', 'src="https://ebank.huidpay.com/${1}">', $resData);
        //         if(is_null($data)){
        //             return $resData;
        //         }
        //         $res_data = preg_replace('/location\.href =\'(.*)\'\;/', 'location.href ="https://ebank.huidpay.com${1}";>', $data);
        //         if(is_null($res_data)){
        //             return $data;
        //         }
        //         return $res_data;
        //     }else{
        //         return $resData;
        //     }

        // }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        /*回掉
        {
            "gmt_create":"2017-10-20 16:32:21",
            "order_no":"201710201632206223221",
            "gmt_payment":"2017-10-20 16:32:21",
            "seller_email":"huidaerwei@gmail.com",
            "notify_time":"2017-10-20 16:32:21",
            "quantity":"1",
            "sign":"700D0CAC0EF6974DC3452262F290B3633C5936B8",
            "discount":"0.00",
            "body":"201710201632206223221",
            "is_success":"T",
            "title":"201710201632206223221",
            "gmt_logistics_modify":"2017-10-20 16:32:21",
            "notify_id":"aa19d8444b7f467b98506a5418bb3280",
            "notify_type":"WAIT_TRIGGER",
            "payment_type":"1",
            "ext_param2":"WXPAY",
            "price":"0.01",
            "total_fee":"0.01",
            "trade_status":"TRADE_FINISHED",
            "trade_no":"101710200383685",
            "signType":"SHA",
            "seller_actions":"SEND_GOODS",
            "seller_id":"100000000002304",
            "is_total_fee_adjust":"0"
        }
        */
        if (isset($params['trade_status']) && $params['trade_status']) {
            $sign = $params['sign'];
            $signType = $params['signType'];

            unset($params['sign']);
            unset($params['signType']);

            if(in_array($params['ext_param2'],['WXPAY', 'ALIPAY', 'QQPAY'])){
                $key = $this->codeKey;
            }else{
                $key = $this->bankKey;
            }
            ksort($params);
            $dataSign = strtoupper(sha1(urldecode(http_build_query($params)).$key));

            if ($sign == $dataSign) {

                return true;
            } else {

               return false;
            }
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
