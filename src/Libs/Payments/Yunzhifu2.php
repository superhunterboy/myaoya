<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//38
class Yunzhifu2 implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            '20001' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            '10001' => '支付宝扫码',
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

    //支付网关地址
    public $getwayUrl = 'https://www.aw5880.cn/pay/action';

    //请求参数
    public $uid   = "57752125"; //"此处填写网站设置中的uid";
    public $token = "d334cb030f2935c306ed47454a8c18dd"; //"此处填写网站设置中的Token";
    public $notify_url; //回调地址
    public $return_url; //通知地址

    public $price; //金额
    public $istype; //支付类型
    public $orderuid  = "none"; //此处传入您网站用户的用户名，方便在我们网站后台查看是谁付的款，强烈建议加上。可忽略。
    public $goodsname = "good_Game";
    public $orderid; //每次有任何参数变化，订单号就变一个吧。
    public $key; //签名

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
            $this->uid        = $conf['parterNo'];
            $this->token      = $conf['parterKey'];
            $this->notify_url = $conf['callbackUrl'];
            $this->return_url = $conf['notifyUrl'];
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
        $this->istype  = $payType;
        $this->price   = $money;
        $this->orderid = $orderId;

        $this->params = [
            'goodsname'  => $this->goodsname,
            'istype'     => $this->istype,
            'notify_url' => $this->notify_url,
            'orderid'    => $this->orderid,
            'orderuid'   => $this->orderuid,
            'price'      => $this->price,
            'return_url' => $this->return_url,
            'uid'        => $this->uid,
        ];
        //签名
        $this->key           = md5($this->goodsname . $this->istype . $this->notify_url . $this->orderid . $this->orderuid . $this->price . $this->return_url . $this->token . $this->uid);
        $this->params['key'] = $this->key;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        try {
            $result = $client->request('POST', $this->getwayUrl, [
                'verify'      => false,
                'headers'     => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                ],
                'form_params' => $this->params,
            ]);
        } catch (GuzzleHttp\Exception $e) {
            return json_encode(['code' => $e->code, 'error' => $e->message], JSON_UNESCAPED_UNICODE);
        }

        if ($result->getStatusCode() == '200') {
            $returnData = strval($result->getBody());
            $data       = json_decode($returnData, true);
            $code       = $data['code'] ?? 1;
            $msg        = $data['msg'] ?? '网络异常!';
            if(empty($data) || $data["code"] != 200){
                return json_encode(['code' => $code, 'error' => $msg], JSON_UNESCAPED_UNICODE);
            }
            $money  = $data["data"]["realprice"] ?? '';
            $qrcode = $data["data"]["qrcode"] ?? '';
            if (!empty($money) && !empty($qrcode)) {
                $qrcodeUrl = Utils::getQrcode($qrcode);
                return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderid . '&fee=' . sprintf("%.2f", $money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
            } else {
                return json_encode(['code' => 1, 'error' => '数据格式错误!'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode(['code' => 1, 'error' => '网络错误，请重试!'], JSON_UNESCAPED_UNICODE);
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $p_id      = $params["ordno"] ?? '';
        $orderid   = $params["orderid"] ?? '';
        $price     = $params["price"] ?? '';
        $realprice = $params["realprice"] ?? '';
        $orderuid  = $params["orderuid"] ?? '';
        $sign      = $params["key"] ?? '';

        $mySign = md5($orderid . $orderuid . $p_id . $price . $realprice . $this->token);

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

}
