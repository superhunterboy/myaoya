<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//40
class Aifu implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
            'WECHAT' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            'ALIPAY' => '支付宝扫码',
        ],
        3  => [ //网银
            'ABC'   => '农业银行',
            'BCCB'  => '北京银行',
            'BOCOM' => '交通银行',
            'BOC'   => '中国银行',
            'BOS'   => '上海银行',
            'CCB'   => '建设银行',
            'CEB'   => '光大银行',
            'CIB'   => '兴业银行',
            'CMBC'  => '民生银行',
            'CMB'   => '招商银行',
            'CNCB'  => '中信银行',
            'GDB'   => '广发银行',
            'HXB'   => '华夏银行',
            'ICBC'  => '工商银行',
            'PAB'   => '平安银行',
            'PSBC'  => '邮政银行',
            'SPDB'  => '浦发银行',
        ],
        4  => [ //QQ扫码
            'QQSCAN' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            'JDSCAN' => '京东扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
        ],
        8  => [ //微信WAP
            'WECHATWAP' => '微信WAP',
        ],
        9  => [ //支付宝WAP
        ],
        10 => [ //QQWAP
            'QQWAP' => 'QQWAP',
        ],
        11 => [ //京东WAP
        ],
        12 => [ //百度WAP
        ],
        13 => [ //银联WAP
        ],
    ];

    //支付网关域名
    public $getwayHost = 'http://pay.ifeepay.com/gateway/pay.jsp';

    //请求参数
    public $merchant_no = '144801006875';
    public $parterKey   = '67b7d54f-f020-11e7-9204-8b36320c9f02';
    public $backend_url; //回调地址
    public $frontend_url; //通知地址

    public $getwayUrl = 'http://pay.ifeepay.com/gateway/pay.jsp';
    public $version   = 'v1'; //接口版本  固定值
    public $order_no; //商户订单号
    public $goods_name = 'goods'; //商品名称   使用base64进行编码（UTF-8编码）
    public $order_amount; //订单金额 以元为单位，精确到小数点后两位
    public $reserve = 'shop'; //自定义返回
    public $pay_mode; //支付模式 01:WEB支付模式
    public $bank_code; //银行编号 银行代号，例如：CCB
    public $card_type = '2'; //允许支付的卡类型 0:借记卡支付 1:信用卡支付 2:借记卡和信用卡
    public $sign; //签名数据 以上请求参数再加上接口秘钥通过md5

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
            $this->merchant_no  = $conf['parterNo'];
            $this->parterKey    = $conf['parterKey'];
            $this->backend_url  = $conf['callbackUrl'];
            $this->frontend_url = $conf['notifyUrl'];
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
        $this->bank_code    = $payType;
        $this->order_amount = $money;
        $this->order_no     = $orderId;
        if ($payType == 'WECHATWAP' || $payType == 'QQWAP') {
            $this->pay_mode = '12';
        } elseif (in_array($payType, ['WECHAT', 'ALIPAY', 'QQSCAN', 'JDSCAN'])) {
            $this->pay_mode = '09';
        } else {
            $this->pay_mode = '01';
        }

        $this->params = [
            'version'      => $this->version,
            'merchant_no'  => $this->merchant_no,
            'order_no'     => $this->order_no,
            'goods_name'   => base64_encode($this->goods_name),
            'order_amount' => $this->order_amount,
            'backend_url'  => $this->backend_url,
            'frontend_url' => $this->frontend_url,
            'reserve'      => $this->reserve,
            'pay_mode'     => $this->pay_mode,
            'bank_code'    => $this->bank_code,
            'card_type'    => $this->card_type,
        ];
        //签名
        $singStr              = urldecode(http_build_query($this->params)) . '&key=' . $this->parterKey;
        $this->sign           = md5($singStr);
        $this->params['sign'] = $this->sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if (in_array($this->bank_code, ['WECHAT', 'ALIPAY', 'QQSCAN', 'JDSCAN'])) {
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
                $code       = $data['result_code'] ?? 1;
                $msg        = $data['result_msg'] ?? '网络异常!';
                if (empty($data) || $code !== '00') {
                    return json_encode(['code' => $code, 'error' => $msg], JSON_UNESCAPED_UNICODE);
                }
                $code_url = $data["code_url"] ?? '';
                if (!empty($code_url)) {
                    $qrcodeUrl = Utils::getQrcode($code_url);
                    return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->order_no . '&fee=' . sprintf("%.2f", $this->order_amount) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($code_url);
                } else {
                    return json_encode(['code' => 1, 'error' => '数据格式错误!'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                return json_encode(['code' => 1, 'error' => '网络错误，请重试!'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            return $this->htmlSend($this->getwayUrl, $this->params);
        }

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign =$params['sign'] ?? '';
        $verify = [
            'merchant_no'     => $params['merchant_no'] ?? '',
            'order_no'        => $params['order_no'] ?? '',
            'order_amount'    => $params['order_amount'] ?? '',
            'original_amount' => $params['original_amount'] ?? '',
            'upstream_settle' => $params['upstream_settle'] ?? '',
            'result'          => $params['result'] ?? '',
            'pay_time'        => $params['pay_time'] ?? '',
            'trace_id'        => $params['trace_id'] ?? '',
            'reserve'         => $params['reserve'] ?? '',
        ];
        $singStr = urldecode(http_build_query($verify)) .'&key='.$this->parterKey;

        return md5($singStr) === $sign;
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
