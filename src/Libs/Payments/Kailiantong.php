<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//31
class Kailiantong implements WMPay
{
    // 支付类型
    public $paymentType = [
        1 => [ //微信扫码
            '20' => '微信',
        ],
        2 => [ //支付宝扫码
            '22' => '支付宝',
        ],
        3 => [ //网银
            'cmb'    => '招商银行',
            'icbc'   => '工商银行',
            'ccb'    => '建设银行',
            'abc'    => '农业银行',
            'comm'   => '交通银行',
            'boc'    => '中国银行',
            'cmbc'   => '民生银行',
            'spdb'   => '浦发银行',
            'hxb'    => '华夏银行',
            'cgb'    => '广发银行',
            'cib'    => '兴业银行',
            'ceb'    => '光大银行',
            'citic'  => '中信银行',
            'pingan' => '平安银行',
            'bob'    => '北京银行',
            'bos'    => '上海银行',
            'psbc'   => '邮政储蓄',
        ],
        4 => [ //QQ扫码
            '46' =>'QQ钱包',
        ],
        5 => [ //京东扫码
        ],
        6 => [ //百度扫码
        ],
        7 => [ //银联扫码
        ],
        8 => [//微信WAP
            '48' => '微信WAP',
        ],
        9 => [//支付宝WAP
            '38' => '支付宝WAP',
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
    public $getwayUrl = 'https://pg.openepay.com/gateway/index.do';
    //wap网关地址
    public $wapUrl    = 'https://mobile.openepay.com/mobilepay/index.do';

    //请求参数
    public $merchantId;     //商户编号
    public $parterKey;      //商户md5key
    public $receiveUrl;     //回调地址
    public $pickupUrl;      //通知地址

    public $inputCharset = '1';             //字符集 1代表UTF-8、2代表GBK、3代表GB2312；
    public $version = 'v1.0';               //网关接收支付请求接口版本 固定为v1.0
    public $language = '1';  //网关页面显示语言种类 1代表简体中文、2代表繁体中文、3代表英文
    public $signType = '0';  //签名类型 0表示订单上送和交易结果通知都使用MD5进行签名 1表示商户用使用MD5算法验签上送订单，开联交易结果通知使用证书签名
    public $orderNo;         //商户订单号 只允许使用字母、数字、- 、_,并以字母或数字开头；每商户提交的订单号，必须在当天的该商户所有交易中唯一
    public $orderAmount;     //商户订单金额  整型数字，金额与币种有关（生产环境金额大于1毛1（11））如果是人民币，则单位是分，即10元提交时金额应为1000如果是美元，单位是美分，即10美元提交时金额为1000
    public $orderCurrency = '156';          //订单金额币种类型  156代表人民币、840代表美元、344代表港币
    public $orderDatetime;                  //商户订单提交时间  日期格式：yyyyMMDDhhmmss，例如：20121116020101
    public $productName = 'naughty_sun';    //商品名称   英文或中文字符串，请勿首尾有空格字符
    public $payType;        //支付方式   用户在支付时可以使用的支付方式，固定选择值：
    public $issuerId;       //具体方式
    public $signMsg;        //签名字符串 签名串生成机制：按上述顺序所有非空参数与密钥key组合，经加密后生成signMsg；
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
            $this->merchantId   = $conf['parterNo'];
            $this->parterKey    = $conf['parterKey'];
            $this->receiveUrl   = $conf['callbackUrl'];
            $this->pickupUrl    = $conf['notifyUrl'];
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
        $this->payType          = $payType;
        $this->orderAmount      = $money * 100;
        $this->orderNo          = $orderId;
        $this->orderDatetime    = date('YmdHis');
        if ($payType == '20') {
            $this->issuerId = 'wechat';
        } elseif ($payType == '22') {
            $this->issuerId = 'alipay';
        } elseif ($payType == '46') {
            $this->issuerId = 'qqpay';
        } elseif ($payType == '48') {
            $this->issuerId = '';
        } elseif ($payType == '38') {
            $this->issuerId = '';
        } else {
            $this->issuerId = $payType;
            $this->payType  = '99';
        }

        $this->params = [
            'inputCharset'  => $this->inputCharset,       //字符集 1代表UTF-8、2代表GBK、3代表GB2312；
            'pickupUrl'     => $this->pickupUrl,          //通知地址
            'receiveUrl'    => $this->receiveUrl,         //回调地址
            'version'       => $this->version,            //网关接收支付请求接口版本 固定为v1.0
            'language'      => $this->language,           //网关页面显示语言种类 1代表简体中文、2代表繁体中文、3代表英文
            'signType'      => $this->signType,           //签名类型 0表示订单上送和交易结果通知都使用MD5进行签名
            'merchantId'    => $this->merchantId,         //商户编号
            'orderNo'       => $this->orderNo,            //商户订单号
            'orderAmount'   => $this->orderAmount,        //商户订单金额 单位是分
            'orderCurrency' => $this->orderCurrency,      //订单金额币种类型  156代表人民币、840代表美元、344代表港币
            'orderDatetime' => $this->orderDatetime,      //商户订单提交时间  日期格式：yyyyMMDDhhmmss
            'productName'   => $this->productName,        //商品名称
            'payType'       => $this->payType,            //支付方式
            'issuerId'      => $this->issuerId,           //具体方式
        ];
        //签名
        if (empty($this->params['issuerId'])) {
            $issuerId = array_slice($this->params, -1, 1);
        }
        $md5Str = urldecode(http_build_query($this->params));
        $this->sign                 = strtoupper(md5($md5Str .'&key='. $this->parterKey));
        $this->params['signMsg']    = $this->sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $this->params['url'] = $this->getwayUrl;
        if ($this->params['payType'] == '99') {
            return $this->htmlSend('http://fuqian.hnbtjy.cn/', $this->params);
        } elseif ($this->params['payType'] == '48' || $this->params['payType'] == '38') {
            $this->params['url'] = $this->wapUrl;
            return $this->htmlSend('http://fuqian.hnbtjy.cn/', $this->params);
        } else {
            $client = new Client();
            try {
                $result = $client->request('POST', $this->getwayUrl, [
                'verify'=>false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                    'Referer'    => 'http://fuqian.hnbtjy.cn/'
                ],
                'form_params' => $this->params
            ]);
            } catch (GuzzleHttp\Exception $e) {
                return json_encode(['code'=>$e->code, 'error'=>$e->message], JSON_UNESCAPED_UNICODE);
            }
            if ($result->getStatusCode() == '200') {
                $returnData = strval($result->getBody());
                $data   = Utils::xmlToArray($returnData);
                if (isset($data['html'])) {
                    return $this->htmlSend('http://fuqian.hnbtjy.cn/', $this->params);
                }
                $response = $data['response'] ?? [];
                $qrcode = $response['message']['qrCode'] ?? false;
                $sign   = $response['signMsg'] ?? '';
                if ($qrcode) {
                    $qrcodeUrl = Utils::getQrcode($qrcode);
                    return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->orderAmount/100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
                } else {
                    return json_encode(['code'=>'1', 'error'=>'数据格式错误!'], JSON_UNESCAPED_UNICODE);
                }

            } else {
                return json_encode(['code'=>'1', 'error'=>'网络异常!'], JSON_UNESCAPED_UNICODE);
            }
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['signMsg'] ?? '';
        foreach ($params as $key => $value) {
            if ($key == 'signMsg' || $value === '') {
                unset($params[$key]);
            }
        }
        $order = [
            'merchantId','version','language','signType','payType','issuerId','mchtOrderId','orderNo','orderDatetime','orderAmount','payDatetime',
            'ext1','ext2','payResult',
        ];
        foreach ($order as  $value) {
            if (isset($params[$value])) {
                $data[$value] = $params[$value];
            }
        }
        $md5Str = urldecode(http_build_query($data));
        $mySign = strtoupper(md5($md5Str .'&key='. $this->parterKey));

        if ($mySign === $sign) {
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

    /*
     * html 表单提交
     */
    private function htmlSend($url, $data, $method = 'post')
    {
        if ($method == 'get') {
            return $url .'?' .http_build_query($data);
        } elseif ($method == 'post') {
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $url . '" method="post" name="orderForm">';
            foreach ($data as $key => $value) {
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
}