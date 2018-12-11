<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Pppay implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'wechat' => 'PC微信扫码',
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
    public $getwayUrl = 'https://pppay.io/api.php'; # 支付网关地址

    public $goodsName; #   商品名称
    public $goodsAmount; #    商品价格
    public $callbackurl; #   下行异步通知地址
    public $hrefbackurl; #   下行同步通知地址(付款完毕后返回,可空):

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
            $this->parter      = $conf['parterNo'];
            $this->key         = $conf['parterKey'];
            $this->callbackurl = $conf['callbackUrl'];
            $this->hrefbackurl = $conf['notifyUrl'];
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
        $i             = mt_rand(1, 99);
        if ($i < 10) {
            $i = '0' . $i;
        }
        $this->goodsAmount = ($money . $i) / 100;

        $this->params = [
            'act'           => 'preOrder',
            'partnerId'     => $this->parter,
            'orderId'       => $orderId,
            'paymentMethod' => $payType,
            'currency'      => 'CNY',
            'goodsName'     => 'VipCard',
            'goodsAmount'   => $this->goodsAmount,
            'qrcodeContent' => '1',
            'ext'           => 'supermarket',
            'noticeUrl'     => $this->callbackurl,
        ];

        $this->params['sign'] = $this->getSignStr($this->params, $this->key);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);
        if ($res->getStatusCode() == '200') {
            $returnStr = $res->getBody()->getContents();
            if (Utils::isJSON($returnStr)) {
                $resArr = json_decode($returnStr, true);

                // file_put_contents('C:\wwwroot\pay\src\1.txt', var_export($resArr,ture));
                $code = $resArr['code'] ?? '';
                $msg  = $resArr['data'] ?? '';
                if ($code == '0') {
                    $payQRCode = $resArr['data']['qrcodeContent'] ?? '';
                    $qrcodeUrl = Utils::getQrcode($payQRCode);

                    echo 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $resArr['data']['preOrderId'] . '&fee=' . $this->goodsAmount . '&qrcode=' . $qrcodeUrl . '&codeurl=' . $payQRCode;
                    // echo 'http://pay.test/payment/scancode?trade_no=' . $resArr['data']['preOrderId'] . '&fee=' . $this->goodsAmount . '&qrcode=' . $qrcodeUrl . '&codeurl=' . $payQRCode;
                } else {
                    if (empty($code)) {
                        $code = 404;
                    }
                    return json_encode(['code' => $code, 'error' => $msg], JSON_UNESCAPED_UNICODE);
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign_type'], $params['sign']);
        $mySign = $this->getSignStr(($params) , $this->key);
        if ($sign === $mySign) {
            return ture;
        }
        return false;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    /**
     * getSignStr 签名生成
     */
    private function getSignStr($param, $md5Key)
    {
        ksort($param, SORT_STRING);
        $signStr = "";
        $tmpArr  = [];
        foreach ($param as $k => $v) {
            $tmpArr[] = $k . "=" . $v;
        }
        $signStr = implode('&', $tmpArr);
        $signStr .= $md5Key;
        $sign = md5($signStr);

        return $sign;
    }

}
