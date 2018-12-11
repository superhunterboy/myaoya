<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Shunxin implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            'wxpay' => '微信扫码',
        ],
        2  => [ //支付宝扫码
        ],
        3  => [ //网银
            'BOC'  => '中国银行',
            'ICBC' => '中国工商银行',
            'ABC'  => '中国农业银行',
            'CCB'  => '中国建设银行',
            'COMM' => '交通银行',
            'CMB'  => '招商银行',
            'CMBC' => '中国民生银行',
            'CIB'  => '兴业银行',
            'SPDB' => '上海浦东发展银行',
            'GDB'  => '广发银行',
            'CNCB' => '中信银行',
            'CEB'  => '中国光大银行',
            'PSBC' => '中国邮政储蓄银行',
            'PAB'  => '平安银行',
            'HXB'  => '华夏银行',
        ],
        4  => [ //QQ扫码
            'qqpay' => 'QQ扫码',
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
    public $getwayUrl = 'http://trade.fjjxjj.com/cgi-bin/netpayment/pay_gate.cgi';

    /**
     * 商户编号
     * @var string
     */
    public $parterNo;

    /**
     * 商户key
     * @var string
     */
    public $parterKey;

    /**
     * 回调地址
     * @var string
     */
    public $callbackUrl;

    /**
     * 通知地址
     * @var string
     */
    public $notifyUrl;

    /**
     * 签名
     * @var string
     */
    public $sign;

    /**
     * 提交参数
     * @var array
     */
    public $params = [];

    public $payType;

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
            $this->parterNo    = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
            $this->callbackUrl = $conf['callbackUrl'];
            $this->notifyUrl   = $conf['notifyUrl'];
        }
    }

    private function __clone()
    {
    }

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
        $this->payType = $payType;
        $this->orderNo = $orderId;
        $this->money   = $money;

        $apiName = 'WEB_PAY_B2C';
        if ($this->payType == 'wxpay') {
            $apiName = 'WECHAT_PAY';
        } elseif ($this->payType == 'qqpay') {
            $apiName = 'QQ_PAY';
        }

        $this->params = [
            'apiName'      => $apiName,
            'apiVersion'   => '1.0.0.0',
            'platformID'   => $this->parterNo,
            'merchNo'      => $this->parterNo,
            'orderNo'      => $this->orderNo,
            'tradeDate'    => date('Ymd'),
            'amt'          => sprintf("%.2f", $this->money),
            'merchUrl'     => $this->callbackUrl,
            'merchParam'   => '',
            'tradeSummary' => '充值',
        ];

        if (!in_array($this->payType, ['wxpay', 'qqpay'])) {
            $this->params['bankCode'] = $this->payType; // 网银，不参与签名
        } else {
            $this->params['customerIP'] = '127.0.0.1'; // 微信、QQ
        }

        $this->params['signMsg'] = $this->getSignStr($this->params);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if (in_array($this->payType, ['wxpay', 'qqpay'])) {
            $client = new Client();
            $result = $client->request('POST', $this->getwayUrl, [
                'verify'      => false,
                'headers'     => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                ],
                'form_params' => $this->params,
            ]);
            if ($result->getStatusCode() == '200') {
                $returnData        = $result->getBody()->getContents();
                $tmpArr            = Utils::xmlToArray($returnData);
                $resArr            = $tmpArr['moboAccount']['respData'] ?? [];
                $resArr['signMsg'] = $tmpArr['moboAccount']['signMsg'] ?? '';
                $resArr['signXml'] = $returnData;
                if ($this->verifySign($resArr)) {
                    unset($resArr['signXml']);
                    if ($resArr['respCode'] == '00' && isset($resArr['codeUrl'])) {
                        $code_url  = base64_decode($resArr['codeUrl']);
                        $qrcodeUrl = Utils::getQrcode($code_url);
                        return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($code_url);
                    } else {
                        return json_encode([
                            'error' => $resArr['respDesc'],
                            'order' => $this->orderNo,
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }
                return json_encode([
                    'code'  => 1,
                    'error' => '验签失败',
                ], JSON_UNESCAPED_UNICODE);
            }
            return json_encode([
                'code'  => 1,
                'error' => '网络错误',
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $tmp = '<html>';
            $tmp .= '<head>';
            $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $tmp .= '<title>Pay Page</title>';
            $tmp .= '</head>';
            $tmp .= '<body style="display:none;">';
            $tmp .= '<form action="' . $this->getwayUrl . '" method="post" name="orderForm">';
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
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $signMsg = $params['signMsg'];
        unset($params['signMsg']);
        $signXml = '';
        if (isset($params['signXml'])) {
            $signXml = $this->filterResponseXml($params['signXml']);
        } elseif (isset($params['notifyType'])) {
            unset($params['notifyType']);
            $signXml = urldecode(http_build_query($params));
        }
        $signStr = $signXml . $this->parterKey;
        return $signMsg == strtoupper(md5($signStr));
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        unset($params['bankCode']);
        unset($params['signMsg']);
        $uri = urldecode(http_build_query($params));
        $uri = $uri . $this->parterKey;
        return strtoupper(md5($uri));
    }

    private function filterResponseXml($xml = '')
    {
        // 剔除xml头
        $xml = substr($xml, strpos($xml, '<moboAccount>'));
        // 剔除标签 <moboAccount> 和 </moboAccount>
        $xml = preg_replace('#\<(\/)?moboAccount.*?\>#i', '', $xml);
        // 剔除 <signMsg>65B6E0CF8C9C4A9112531CEF5F99932D</signMsg>
        $xml = preg_replace('#\<signMsg.*?\>.*\<(\/)?signMsg.*?\>#i', '', $xml);

        return $xml;
    }
}
