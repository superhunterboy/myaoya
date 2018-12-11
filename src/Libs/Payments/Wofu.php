<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Wofu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            'weixin_scan' => '微信',
        ],
        2 => [
            'alipay_scan' => '支付宝',
        ],
        3 => [
        'ABC'             => '农业银行',
        'ICBC'            => '工商银行',
        'CCB'             => '建设银行',
        'BCOM'            => '交通银行',
        'BOC'             => '中国银行',
        'CMB'             => '招商银行',
        'CMBC'            => '民生银行',
        'CEBB'            => '光大银行',
        'BOB'             => '北京银行',
        'SHB'             => '上海银行',
        'NBB'             => '宁波银行',
        'HXB'             => '华夏银行',
        'CIB'             => '兴业银行',
        'PSBC'            => '中国邮政银行',
        'SPABANK'         => '平安银行',
        'SPDB'            => '浦发银行',
        'ECITIC'          => '中信银行',
        'HZB'             => '杭州银行',
        'GDB'             => '广发银行',
        ],
        4 => [
            'tenpay_scan' => 'QQ钱包',
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
    public $getwayUrl = [
        'codepay'   => 'https://api.5wpay.net/gateway/api/scanpay',
        'netbank' => 'https://pay.5wpay.net/gateway?input_charset=UTF-8',
    ];

    //请求网关
    public $requestUrl;

    //银行扫码公用参数
    public $merchant_code;       //商家号 String(13)  必填  商户签约时，W付支付平台分配的唯一商家号。举例：800001001002。
    public $service_type;        //业务类型    String(10)  必选  固定值： 或 或
    public $notify_url;          //服务器异步通知地址   String(200) 必选  支付成功后，W付支付平台会主动通知商家系统，商家系统必须指定接收通知的地址。举例：http://www.5wpay.net/Notify_Url.jsp
    public $interface_version;   //接口版本    String(10)  必选  接口版本，固定值：V3.1(必须大写)
    public $client_ip;           //客户端IP   String(15)  必选  消费者创建交易时所使用机器的IP或者终端ip，最大长度为15个字符。举例：192.168.1.25
    public $sign_type = "RSA-S"; //签名方式    String(10)  必选  RSA或RSA-S，不参与签名
    public $sign;                //签名  String  必选  签名数据，具体请见附录的签名规则定义。业务参数
    public $order_no;            //商户网站唯一订单号   String(64)  必选  商户系统订单号，由商户系统生成,保证其唯一性，最长100位,由字母、数字组成.举例：1000201666。
    public $order_time;          //商户订单时间  Date    必选  商户订单时间，格式：yyyy-MM-dd HH:mm:ss，举例：2013-11-01 12:34:58
    public $order_amount;        //商户订单总金额 Number(13,2)    必选  该笔订单的总金额，以元为单位，精确到小数点后两位。举例：12.01。
    public $product_name;        //商品名称    String(100) 必选  商品名称，不超过100个字符。举例：华硕G750Y47JX-BL。
    public $product_code;        //商品编号    String(60)  可选  商品编号，不超过60个字符，举例：华硕B85M-ER2.0
    public $product_num;         //商品数量    Number(10)  可选  商品数量，必须是数字
    public $product_desc;        //商品描述    String(300) 可选  商品描述，不超过300个字符。
    public $extra_return_param;  //公用回传参数  String(100) 可选  商户如果支付请求时传递了该参数，则通知商户支付成功时会回传该参数。
    public $extend_param;        //公用业务扩展参数    String  可选  格式：参数名1^参数值|参数名2^参数值2说明：多条数据间用"|"间隔 举例：name^张三|sex^男

    //银行参数
    public $return_url;                 //页面跳转同步通知地址
    public $input_charset = 'UTF-8';    //String(5)   √   参数名称：参数编码字符集 取值：UTF-8、GBK(必须大写)
    public $pay_type = 'b2c';           //String(10)  ×   参数名称：支付类型 取值如下（必须小写，多选时请用逗号隔开）b2c(网银),weixin(微信),alipay_scan(支付宝),tenpay_scan(qq扫码)
    public $bank_code;                  //String(10)  ×   参数名称：网银直连银行代码 参见附录中的银行代码对照表，当该参数为空或与对照表中银行编码不一致时，直接跳转到W付收银台选择银行页面
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
            $this->merchant_code        = $conf['parterNo'];
            $this->parterKey            = $conf['parterKey']; // {"pubkey":"","prikey":"","wpaykey":""}
            $this->return_url           = $conf['callbackUrl']; // 网银
            //$this->pay_show_url = $conf['callbackUrl']; // 微信
            $this->notify_url           = $conf['notifyUrl']; // 微信、支付宝、网银
            if ($this->parterKey) {
                $parterKey              = json_decode($this->parterKey, true);
                $this->pubkey           = $parterKey['pubkey'];
                $this->prikey           = $parterKey['prikey'];
                $this->wpaykey          = $parterKey['wpaykey'];
            }
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
        $this->order_amount     = $money;
        $this->order_no         = $orderId;
        $this->product_name     = $orderId;
        $this->order_time       = date('Y-m-d H:i:s');
        $this->bank_code        = $payType;
        $this->client_ip        = Utils::getIp();
        if (!in_array($payType, ['weixin_scan', 'alipay_scan', 'tenpay_scan'])) {
            // 网银
            $this->params['return_url']   = $this->return_url;
            $this->params['input_charset']    = $this->input_charset;
            $this->params['pay_type'] = $this->pay_type;
            $this->params['bank_code']    = $this->bank_code;
            $this->params['service_type']    = 'direct_pay';
            $this->params['interface_version']      = 'V3.0';
        }else{
            $this->params['service_type']    = $this->bank_code;
            $this->params['interface_version']      = 'V3.1';
        }
            // 扫码&银行公用
            $this->params['merchant_code']          = $this->merchant_code;
            $this->params['notify_url']             = $this->notify_url;
            $this->params['order_no']               = $this->order_no;
            $this->params['order_time']             = $this->order_time;
            $this->params['order_amount']           = $this->order_amount;
            $this->params['product_name']           = $this->product_name;
            if(!empty($this->client_ip)) {
                $this->params['client_ip']          = $this->client_ip;
            }
            //排序&签名
            $this->params = Utils::mySort($this->params);
            //$merchant_private_key= openssl_pkey_get_private(Utils::formatRsaKey($this->prikey, 'priKey'));
            //openssl_sign(http_build_query($params), $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
            $this->sign = Utils::rsaSign_string(urldecode(http_build_query($this->params)), $this->prikey, OPENSSL_ALGO_MD5);
            $this->params['sign'] = $this->sign;
            $this->params['sign_type']     = $this->sign_type;

        if (!in_array($payType, ['weixin_scan', 'alipay_scan', 'tenpay_scan'])) {
            $this->isNetBank          = true;
            $this->requestUrl         = $this->getwayUrl['netbank'];
        } else {
            $this->requestUrl         = $this->getwayUrl['codepay'];
        }
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if (isset($this->isNetBank)) {
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $this->requestUrl . '" method="post" name="orderForm">';
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
            $client = new Client();
            $res    = $client->request('POST', $this->requestUrl, ['verify' => false, 'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'], 'form_params' => $this->params]);
            if ($res->getStatusCode() == '200') {
                $resData = $res->getBody();
                //return $resData;
                if ($resData) {
                    $returnArr = Utils::toArray($resData, 3);
                    $qrcode   = $returnArr['qrcode'] ?? '';

                    if (empty($qrcode)) {
                        return false;
                    }

                    if ($this->bank_code == 'alipay_scan' && array_key_exists("isRedirect", $returnArr)) {
                       return urldecode($qrcode);
                    }

                        $qrcodeUrl = Utils::getQrcode($qrcode);
                        return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->order_no . '&fee=' . sprintf("%.2f", $this->order_amount) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);

                }
            }else{
                return false;
            }
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        /*回掉
        {   "trade_no":"1004070440",
            "sign_type":"RSA-S",
            "notify_type":"offline_notify",
            "merchant_code":"501502002027",
            "order_no":"201710101602419010243",
            "trade_status":"SUCCESS",
            "sign":"EX7ZgtMUUxyBv3sAXc4+BG9aILU82VSnarf4PZ95rbOqD6+HnUXmqIE8u9dRn1SI7nxLiOh07aMV1dmURXQqGZqMZpFhU3f+vHM/NPsN43XTw58e9iJ4j95GjSPnNqO+6zFnLSD09IQOlz+73OQs6zqPQ1/EH6H4DpwF1KaGpWk=",
            "order_amount":"0.01",
            "interface_version":"V3.0",
            "bank_seq_no":"C1074647138",
            "order_time":"2017-10-10 16:02:43",
            "notify_id":"d0387779427a47598357ca5eecbd3ff3",
            "trade_time":"2017-10-10 16:02:43"
        }*/
        if (isset($params['trade_status']) && $params['trade_status']) {
            $sign = $params['sign'];
            $sign_type = $params['sign_type'];

            unset($params['sign']);
            unset($params['sign_type']);
            $data = Utils::mySort($params);
            $data = urldecode(http_build_query($data));

            if (Utils::rsaVerify_string($data, $this->wpaykey, $sign, OPENSSL_ALGO_MD5)) {

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
