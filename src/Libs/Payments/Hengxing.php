<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Weiming\Libs\Payments\WMPay;

//45
class Hengxing implements WMPay
{
    // 支付类型
    public $paymentType = [
        1  => [ //微信扫码
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
            'unionPayQR' => '银联扫码',
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
    public $getwayUrl = 'http://paygw.oracn.net/union/qrPay';

    //请求参数
    public $merchantId; //商户编号
    public $parterKey; //商户md5key
    public $backNotifyUrl; //回调地址
    public $notifyUrl; //通知地址(没有通知地址)

    public $charset = 'UTF-8'; //字符集
    public $version = '1.0'; //版本号
    public $businessType; //业务类型
    public $orderId; //订单号
    public $tranTime; //交易时间
    public $tranAmt; //金额
    public $signType = 'MD5'; //加密类型
    public $signData; //签名
    public $postData; //请求数据
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
            $this->merchantId    = $conf['parterNo'];
            $this->parterKey     = $conf['parterKey'];
            $this->backNotifyUrl = $conf['callbackUrl'];
            $this->notifyUrl     = $conf['notifyUrl'];
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
        $this->tranAmt      = $money;
        $this->orderId      = $orderId;
        $this->businessType = $payType;

        $this->params = [
            'charset'       => $this->charset,
            'version'       => $this->version,
            'businessType'  => $this->businessType,
            'merchantId'    => $this->merchantId,
            'orderId'       => $this->orderId,
            'tranTime'      => date('YmdHis', time()),
            'tranAmt'       => $this->tranAmt,
            'backNotifyUrl' => $this->backNotifyUrl,
        ];
        //签名
        $md5Str                   = urldecode(http_build_query($this->params));
        $this->signData           = strtoupper(md5($md5Str . '&' . $this->parterKey));
        $this->params['signType'] = $this->signType;
        $this->params['signData'] = $this->signData;
        //生成请求数据
        $this->postData = base64_encode(json_encode($this->params));

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
                'verify'  => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                ],
                'body'    => $this->postData,
            ]);
        } catch (RequestException $e) {
            return json_encode(['code' => '1', 'error' => strstr($e->getMessage(), "\n", true)], JSON_UNESCAPED_UNICODE);
        }
        if ($result->getStatusCode() == '200') {
            $returnData = strval($result->getBody());
            $data       = json_decode($returnData, true);
            $returnCode = $data['status'] ?? 1;
            $returnMsg  = $data['msg'] ?? '数据错误!';
            if ($returnCode != 0) {
                return json_encode(['code' => $returnCode, 'error' => $returnMsg], JSON_UNESCAPED_UNICODE);
            }
            $orderId = $data['orderId'] ?? '';
            $qrcode  = $data['codeUrl'] ?? '';
            $imgcode = $data['codeImgUrl'] ?? '';
            $money   = $data['tranAmt'] ?? $this->tranAmt;
            if (!empty($qrcode) && $orderId == $this->orderId) {
                return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderId . '&fee=' . sprintf("%.2f", $money) . '&qrcode=' . urlencode($imgcode) . '&codeurl=' . urlencode($qrcode);
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
        $sign = $params['signData'] ?? '';
        if (isset($params['signData'])) {
            unset($params['signData']);
        }
        if (isset($params['signType'])) {
            unset($params['signType']);
        }
        foreach($params as $key => $value){
            if (empty($params[$key])) {
                unset($params[$key]);
            }
        }
        $md5Str = urldecode(http_build_query($params));
        $mySign = strtoupper(md5($md5Str . '&' . $this->parterKey));

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
