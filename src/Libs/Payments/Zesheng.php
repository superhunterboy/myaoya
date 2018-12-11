<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Zesheng implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '21' => '微信',
        ],
        2 => [
            '30' => '支付宝',
        ],
        3 => [
            'ICBC'    => '中国工商银行',
            'CMB'     => '招商银行',
            'ABC'     => '中国农业银行',
            'CCB'     => '中国建设银行',
            'BCCB'    => '北京银行',
            'BCM'     => '交通银行',
            'CIB'     => '兴业银行',
            'CMBC'    => '中国民生银行',
            'CEB'     => '光大银行',
            'BOC'     => '中国银行',
            'PAB'     => '平安银行',
            'CITIC'   => '中信银行',
            'GDB'     => '广发银行',
            'PSBC'    => '中国邮政',
        ],
        4 => [
            '31' => 'QQ钱包',
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
        'codepay'   => 'http://payment.zsagepay.com/scan/entrance.do',
        'netbank' => 'http://payment.zsagepay.com/ebank/pay.do',
    ];

    //请求网关
    public $requestUrl;

    //银行扫码公用参数
    public $merchantCode; //商户号 String 泽圣分配的商户号 是 是
    public $parterKey; //商户key
    public $outOrderId; //商户订单号 String 商户系统唯一的订单编号 是 是
    public $orderCreateTime; //商户订单创建时间 String yyyyMMddHHmmss 是 是
    public $lastPayTime; //最晚支付时间 String yyyyMMddHHmmss
    public $noticeUrl; //通知商户服务端地址 String 是
    public $sign; //签名 String 签名机制 是

    //扫码参数
    public $model = 'QR_CODE'; //模块名 String 传入固定值:QR_CODE 是
    public $amount; //支付金额 Long 单位分 是 是
    public $isSupportCredit = 1; //是否支持信用卡 int 是否支持信用卡：1– 是（默认）0-否 是 是
    public $ip; //ip 地址 String app 和网页支付提交用户端 ip 是
    public $payChannel; //渠道编码 String 21 微信，30-支付宝，31-QQ 钱包不传值则默认 21

    //银行参数
    public $totalAmount; //支付金额 Long 单位分 是 是
    public $merUrl; //商户取货URL String 如果在商户端选择银行时，必填 是
    public $bankCode; //支付银行代码 String 看 5.1 支持银行列表 是
    public $bankCardType = '01'; //支付银行卡类型 String 00：B2C 借贷记综合 01：B2C 纯借记 03：B2B 企业网银 是

    //内部参数
    private $isNetBank;
    private $money;


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
            $this->merchantCode        = $conf['parterNo'];
            $this->parterKey            = $conf['parterKey'];
            $this->noticeUrl           = $conf['callbackUrl'];
            $this->merUrl           = $conf['notifyUrl'];
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
        $this->money              = $money * 100;
        $this->outOrderId         = $orderId;
        $this->orderCreateTime    = date('YmdHis');
        $this->ip                 = Utils::getIp();
        if (!in_array($payType, ['21', '30', '31'])) {
            // 网银
            $this->params['totalAmount']        = $this->money;
            $this->params['lastPayTime']            = date("YmdHis", strtotime("+1 days"));
        }else{
            //扫码
            $this->params['amount']             = $this->money;
            $this->params['isSupportCredit']    = $this->isSupportCredit;
            $this->params['noticeUrl']          = $this->noticeUrl;
        }
        // 扫码&银行公用
        $this->params['merchantCode']           = $this->merchantCode;
        $this->params['outOrderId']             = $this->outOrderId;
        $this->params['orderCreateTime']        = $this->orderCreateTime;

        //排序&签名
        ksort($this->params);
        $data = urldecode(http_build_query($this->params));
        $this->sign = strtoupper(md5($data . '&KEY=' . $this->parterKey));
        $this->params['sign'] = $this->sign;

        if (!in_array($payType, ['21', '30', '31'])) {
            $this->params['bankCode']           = $payType;
            $this->params['bankCardType']       = $this->bankCardType;
            $this->isNetBank                    = 'bank';
            $this->requestUrl                   = $this->getwayUrl['netbank'];
            $this->params['noticeUrl']          = $this->noticeUrl;
            $this->params['merUrl']             = $this->merUrl;
        } else {
            $this->params['model']              = $this->model;
            $this->params['ip']                 = $this->ip;
            $this->params['payChannel']         = $payType;
            $this->requestUrl                   = $this->getwayUrl['codepay'];
        }

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if ($this->isNetBank === 'bank') {
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
            $res    = $client->request('POST', $this->requestUrl,
                [
                    'verify' => false,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
                    ],
                    'form_params' => $this->params
                ]
            );
            if ($res->getStatusCode() == '200') {
                $resData = $res->getBody();
                //return $resData . $data;
                if ($resData) {
                    $returnArr = json_decode($resData, true);
                    if($returnArr['code'] != '00'){
                        return $returnArr['msg'];
                    }
                    $qrcode   = $returnArr['data']['url'];

                    $qrcodeUrl = Utils::getQrcode($qrcode);
                    return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->outOrderId . '&fee=' . sprintf("%.2f", $this->money/100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
                }
            }else{
                return '网络错误!';
            }
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        /*回掉
        {   merchantCode 商户号 String 是
            instructCode 交易订单号 String 是
            transType 交易类型 String 00200-消费 是
            outOrderId 商户订单 String 是
            transTime 交易时间 String yyyyMMddHHmmss 是
            totalAmount 消费金额 Long 单位 分 是
            ext 扩展字段 String 异步通知原样返回 否
            sign 签名 String
        }*/
        if (isset($params['sign']) && $params['sign']) {
            $sign = $params['sign'];

            unset($params['sign']);
            unset($params['ext']);
            ksort($params);
            $data = urldecode(http_build_query($params));
            $dataSign = strtoupper(md5($data . '&KEY=' . $this->parterKey));

            if ($dataSign == $sign) {

                return true;
            }

            return false;
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
