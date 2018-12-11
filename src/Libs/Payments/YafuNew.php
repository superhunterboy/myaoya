<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class YafuNew implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            '0201' => '微信',
        ],
        2 => [
            '0301' => '支付宝',
        ],
         3 => [
            'ICBC'  => '中国工商银行',
            'CCB'   => '中国建设银行',
            'CMB'   => '招商银行',
            'ABC'   => '中国农业银行',
            'BOCOM' => '交通银行',
            'BOC'   => '中国银行',
            'CEB'   => '光大银行',
            'CMBC'  => '中国民生银行',
            'CIB'   => '兴业银行',
            'CITIC' => '中信银行',
            'CGB'   => '广发银行',
            'SPDB'  => '上海浦东发展银行',
            'PAB'   => '平安银行',
            'HXBC'  => '华夏银行',
            'NBCB'  => '宁波银行',
            'BEA'   => '东亚银行',
            'BOS'   => '上海银行',
            'PSBC'  => '中国邮政储蓄银行',
            'NJCB'  => '南京银行',
            'SRCB'  => '上海农村商业银行',
            'CBHB'  => '渤海银行',
            'BOCD'  => '成都银行',
            'BOBJ'  => '北京银行',
            'WSB'   => '徽商银行',
        ],
        4 => [
            '0501' => 'QQ钱包'
        ],
        5 => [
            '0801' => '京东扫码',
        ],
        6 => [
        ],
        7 => [
            '0701' => '银联扫码',
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

    /**
     * 支付网关地址
     */
    public $getwayUrl = 'http://yf.yafupay.com/yfpay/cs/pay.ac';

    /**
     * 商户编号
     */
    public $consumerNo;

    /**
     * 商户key
     */
    public $parterKey;

    /**
     * 回调地址
     */
    public $backUrl;

    /**
     * 通知地址
     */
    public $frontUrl;

    public $version = '3.0'; // 必须
    public $merOrderNo; // 必须
    public $transAmt; // 必须
    public $bankCode = ''; // 非必须
    public $payType; // 必须
    public $goodsName; // 必须
    public $merRemark = ''; // 非必须
    public $buyIp     = ''; // 非必须
    public $buyPhome  = ''; // 非必须
    public $shopName  = ''; // 非必须

    /**
     * 签名
     */
    public $sign; // 必须

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

            $this->consumerNo = $conf['parterNo'];

            $this->parterKey = $conf['parterKey'];

            $this->backUrl = $conf['callbackUrl'];

            $this->frontUrl = $conf['notifyUrl'];
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
        $this->payType = $payType;
        // 网银
        if (!in_array($payType, ['0201', '0301'])) {
            $this->bankCode  = '0101';
            $this->payType = $payType;
        }
        $this->merOrderNo = $orderId;
        $this->transAmt   = $money;
        $this->goodsName  = $orderId;
        $this->merRemark  = $orderId;

        $this->params = [
            'version'    => $this->version,
            'consumerNo' => $this->consumerNo,
            'merOrderNo' => $this->merOrderNo,
            'transAmt'   => $this->transAmt,
            'backUrl'    => $this->backUrl,
            'frontUrl'   => $this->frontUrl,
            'bankCode'   => $this->bankCode,
            'payType'    => $this->payType,
            'goodsName'  => $this->goodsName,
            'merRemark'  => $this->merRemark,
            'buyIp'      => $this->buyIp,
            'buyPhome'   => $this->buyPhome,
            'shopName'   => $this->shopName,
            'sign'       => $this->sign,
        ];

        $signPars = Utils::getSignStr($this->params);
        $signPars .= "key=" . $this->parterKey;
        $this->sign           = md5($signPars);
        $this->params['sign'] = $this->sign;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        // $client = new Client();

        // $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);

        // if ($res->getStatusCode() == '200') {

        //     $returnJson = $res->getBody();

        //     if (preg_match("/<.*<\/html>/", Utils::formatHtml($returnJson))) {
        //         // 返回表单，自动POST提交
        //         return $returnJson;
        //     } else {

        //     }

        //     // Utils::isZiyoufuSign($returnArr, $this->parterKey);
        // }

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

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        if (Utils::isYafuNew($params, $this->parterKey)) {
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
