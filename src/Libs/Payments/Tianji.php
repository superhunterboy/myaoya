<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//35
class Tianji implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            '02' => '微信扫码',
        ],
        2  => [ //支付宝扫码
        ],
        3  => [ //网银
            '01000000' => '邮储银行',
            //'04403600' => '徽商银行',
            '01020000' => '工商银行',
            '01030000' => '农业银行',
            '01040000' => '中国银行',
            '01050000' => '建设银行',
            '03010000' => '交通银行',
            '03020000' => '中信银行',
            '03030000' => '光大银行',
            '03040000' => '华夏银行',
            '03050000' => '民生银行',
            '03060000' => '广发银行',
            '03070000' => '深发银行',
            '03080000' => '招商银行',
            '03090000' => '兴业银行',
            '03100000' => '浦发银行',
            //'03110000' => '恒丰银行',
            //'03134500' => '齐鲁银行',
            '03160000' => '浙商银行',
            '04012900' => '上海银行',
            //'04023930' => '厦门银行',
            '04031000' => '北京银行',
            //'04044560' => '烟台银行',
            //'04053910' => '福州银行',
            //'04062410' => '长春银行',
            //'04073140' => '镇江银行',
            '04083320' => '宁波银行',
            //'04094510' => '济南银行',
            '04100000' => '平安银行',
            //'04105840' => '深圳银行',
            //'04115010' => '焦作银行',
            //'04123330' => '温州银行',
            //'04135810' => '广州银行',
            //'04145210' => '武汉银行(汉口银行)',

        ],
        4  => [ //QQ扫码
            '010500' => 'QQ扫码',
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
    public $getwayUrl = 'https://pay.veryluckypay.com/pay'; //http://118.126.82.51:8080/pay

    //请求参数
    public $merchant_id; //商户编号
    public $parterKey; //商户md5key
    public $back_url; //回调地址
    public $notifyUrl; //通知地址

    public $order_id; // 商户订单编号
    public $pay_type = "010500"; //支付类型
    public $trans_amt; //订单金额 单位分
    public $goods_title = "Tianji"; //商品名称
    public $goods_desc  = "hammer"; //商品描述
    public $send_ip; //购买者Ip
    public $send_time; //购买 时间
    public $bank_id; //银行代码
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
            $this->merchant_id = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
            $this->back_url    = $conf['callbackUrl'];
            $this->notifyUrl   = $conf['notifyUrl'];
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
        if (in_array($payType, ['02', '010500'])) {
            $this->pay_type = $payType;
            $this->bank_id  = $payType;
        } else {
            $this->pay_type = '04';
            $this->bank_id  = $payType;
        }

        $this->trans_amt = $money * 100;
        $this->order_id  = $orderId;
        $this->send_time = date('YmdHis');
        $this->send_ip   = Utils::getIp();

        $this->params = [
            'merchant_id' => $this->merchant_id,
            'order_id'    => $this->order_id,
            'pay_type'    => $this->pay_type,
            'trans_amt'   => $this->trans_amt,
            'back_url'    => $this->back_url,
            'goods_title' => $this->goods_title,
            'goods_desc'  => $this->goods_desc,
            'send_ip'     => $this->send_ip,
            'send_time'   => $this->send_time,
            'bank_id'     => $this->bank_id,
        ];
        //签名
        ksort($this->params);
        $md5Str               = urldecode(http_build_query($this->params));
        $this->sign           = md5($md5Str . '&key=' . $this->parterKey);
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
                    'Referer'    => 'http://fuqian.hnbtjy.cn/',
                ],
                'form_params' => $this->params,
            ]);
        } catch (RequestException $e) {//GuzzleHttp\
            return json_encode(['code' => '1', 'error' => strstr($e->getMessage(), "\n", true)], JSON_UNESCAPED_UNICODE);
        }
        if ($result->getStatusCode() == '200') {
            $returnData = strval($result->getBody());
            $data       = json_decode($returnData, true);
            $returnCode = $data['ret_code'] ?? 1;
            $returnMsg  = $data['ret_msg'] ?? '数据格式错误!';
            if ($returnCode != 200) {
                return json_encode(['code' => $returnCode, 'error' => $returnMsg], JSON_UNESCAPED_UNICODE);
            }
            $response = $data['result'] ?? [];
            $qrcode   = $response['pay_link'] ?? false;
            $orderId  = $response['order_id'] ?? '';
            $money    = $response['trans_amt'] ?? '';

            if ($qrcode && $orderId == $this->order_id && $money == $this->trans_amt) {
                if ($this->pay_type == '04') {
                    return $qrcode;
                }
                $qrcodeUrl = Utils::getQrcode($qrcode);
                return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->order_id . '&fee=' . sprintf("%.2f", $this->trans_amt / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
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
        ksort($params);
        foreach ($params as $key => $value) {
            if ($key == 'sign' || $value === '') {
                unset($params[$key]);
            }
        }
        $md5Str = urldecode(http_build_query($params));
        $mySign = md5($md5Str . '&key=' . $this->parterKey);

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
