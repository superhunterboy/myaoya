<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Shanrong implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'WECHAT_PC' => '微信扫码支付',
        ],
        2  => [ //支付宝扫码
            'ALIPAY_PC' => '支付宝码支付',
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
            'WECHAT_MOBILE' => '微信移动端支付',
        ],
        9  => [ //支付宝WAP
            'ALIPAY_MOBILE' => '支付宝移动端支付',
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
    public $getwayUrl = 'https://openapi.shanrong.shysrj.com/gateway/pay'; # 支付网关地址

    /**
     * 提交参数
     * @var array
     */
    public $params = [];
    public $orderNo;
    public $goodsAmount;

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
        $this->orderNo     = $orderId;
        $this->goodsAmount = $money;

        $this->params = [
            'merchantCode'   => $this->parter,
            'method'         => $payType,
            'signType'       => 'MD5',
            'dateTime'       => date("YmdHis"),
            'orderNum'       => $this->orderNo,
            'payMoney'       => ($this->goodsAmount) * 100,
            'productName'    => '充值',
            'notifyUrl'      => $this->callbackurl,
            'spbillCreateIp' => $_SERVER['REMOTE_ADDR'],
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
                $code   = $resArr['platRespCode'] ?? '';
                if ($code == 'SUCCESS') {
                    echo $resArr['payUrl'];
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
        $sign = $params['platSign'];
        unset($params['platSign']);
        $mySign = $this->getSignStr(($params), $this->key);
        // $params['mySign'] = $mySign;
        // $params['platSign'] = $sign;
        // file_put_contents('/usr/share/nginx/html/aoya_back/logs/shanrong.txt', var_export($params,true));
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
        $sign = '';
        foreach ($param as $key => $value) {
            $sign .= $value;
        }
        $sign = md5($sign . $md5Key);
        
        // $sign = $sign.$md5Key;
        return $sign;
    }
}
