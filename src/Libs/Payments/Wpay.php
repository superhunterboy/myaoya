<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//44
class Wpay implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            'wx' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'zfb' => '支付宝扫码',
        ],
        3  => [ //网银
        ],
        4  => [ //QQ扫码
            'qq' => 'QQ扫码',
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
    public $getwayUrl = 'http://payment.wpay999.com/payment/pay.aspx';

    //请求参数
    public $cid       = '74611112'; //商户编号
    public $parterKey = 'E883031820401EC31A16A03EF4326D24'; //商户md5key
    public $notify_url; //回调地址
    public $return_url; //通知地址

    public $attach; //附带字段
    public $body = 'funly'; //商品名
    public $create_ip; //客户端ip
    public $out_trade_no; //订单号
    public $paytype; //支付类型
    public $time_expire = ''; //过期时间
    public $time_start  = ''; //开始时间
    public $total_fee; //金额 分
    public $sign; //签名
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
            $this->cid        = $conf['parterNo'];
            $this->parterKey  = $conf['parterKey'];
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
        $this->total_fee    = $money * 100;
        $this->out_trade_no = $orderId;
        $this->attach       = $orderId;
        $this->paytype      = $payType;
        $this->create_ip    = Utils::getIp();

        $this->params = [
            'attach'       => $this->attach,
            'body'         => $this->body,
            'cid'          => $this->cid,
            'create_ip'    => $this->create_ip,
            'notify_url'   => $this->notify_url,
            'out_trade_no' => $this->out_trade_no,
            'paytype'      => $this->paytype,
            'return_url'   => $this->return_url,
            'time_expire'  => $this->time_expire,
            'time_start'   => $this->time_start,
            'total_fee'    => $this->total_fee,
        ];
        //签名
        ksort($this->params);
        $md5Str               = urldecode(http_build_query($this->params));
        $this->sign           = md5(strtolower($md5Str . '&ckey=' . $this->parterKey));
        $this->params['sign'] = $this->sign;

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
        } catch (RequestException $e) {
            return json_encode(['code' => '1', 'error' => strstr($e->getMessage(), "\n", true)], JSON_UNESCAPED_UNICODE);
        }
        if ($result->getStatusCode() == '200') {
            $returnData = strval($result->getBody());
            $data       = json_decode($returnData, true);
            $returnCode = $data['errcode'] ?? 1;
            $returnMsg  = $data['msg'] ?? '数据格式错误!';
            if ($returnCode != 0) {
                return json_encode(['code' => $returnCode, 'error' => $returnMsg], JSON_UNESCAPED_UNICODE);
            }
            $url = $data['url'] ?? '';
            if (!empty($url)) {
                return $url;
            } else {
                return json_encode(['code' => '1', 'error' => '数据错误!'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode(['code' => '1', 'error' => '网络异常!'], JSON_UNESCAPED_UNICODE);
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = $params['sign'] ?? '';
        if (!empty($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $md5Str = urldecode(http_build_query($params));
        $mySign = md5(strtolower($md5Str . '&ckey=' . $this->parterKey));

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
            return $url . '?' . http_build_query($data);
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
