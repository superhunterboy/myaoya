<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Changzhifu implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            '10' => 'PC微信扫码',
        ],
        2  => [ //支付宝扫码
            '020' => '支付宝扫码',
        ],
        3  => [ //网银
            '1001' => '工商银行',
            '1002' => '中国银行',
            '1003' => '招商银行',
            '1004' => '建设银行',
            '1005' => '交通银行',
            '1006' => '农业银行',
            '1007' => '邮政储蓄银行',
            '1008' => '中信银行',
            '1009' => '民生银行',
            '1010' => '浦发银行',
            '1011' => '光大银行',
            '1012' => '平安银行',
            '1013' => '兴业银行',
            '1014' => '华夏银行',
            '1015' => '北京银行',
            '1016' => '上海银行',
            '1017' => '广发银行',
            '1018' => '北京农村商业银行',
            '1019' => '晋中银行',
            '1020' => '杭州银行',
            '1021' => '宁波银行',
            '1022' => '深圳发展银行',
            '1023' => '农村信用社',
            '1024' => '徽商银行',
            '1025' => '渤海银行',
            '1026' => '重庆三峡银行',
            '1027' => '长沙银行',
        ],
        4  => [ //QQ扫码
            '31' => 'QQ扫码',
        ],
        5  => [ //京东扫码
            '41' => '京东扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            '0002' => '银联扫码',
        ],
        8  => [ //微信WAP
            '11' => 'H5微信',
        ],
        9  => [ //支付宝WAP
            '022' => 'H5支付宝',
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
    public $getwayUrl = 'https://n-sdk.retenai.com/'; // 请求url

    public $sign_type = 'md5'; // 加密类型
    public $mch_id; // 商户ID
    public $mch_order; // 商户订单号
    public $amt; // 金额(单位：厘 1元=1000厘)
    public $remark = 'abc'; // 订单内容
    public $created_at; // 订单创建时间
    public $client_ip; // 终端ip
    public $notify_url; // 回调通知url
    public $mch_key; // 商户秘钥
    public $callback_url; // 下行同步通知地址(付款完毕后返回,可空)
    public $sign; // 签名 sign 是 string(32) MD5签名结果，详见“sign安全规范”
    public $pay_type; // 支付类型
    public $bank_code; // 银行code

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
            $this->mch_id       = $conf['parterNo'];
            $this->mch_key      = $conf['parterKey'];
            $this->callback_url = $conf['callbackUrl'];
            $this->notify_url   = $conf['notifyUrl'];
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
        $this->amt     = $money * 1000; // 厘
        $payType       = intval($payType);
        if ($payType == 2) {
            $this->getwayUrl .= 'api/v1/union_qrcode.api';
        } elseif ($payType == 3) {
            $this->getwayUrl .= 'api/v1/quick.api';
        } elseif ($payType == 4) {
            $this->getwayUrl .= 'api/v1/quick_page.api';
        } elseif ($payType == 10) {
            $this->getwayUrl .= 'api/v1/wx_qrcode.api';
        } elseif ($payType == 20) {
            $this->getwayUrl .= 'api/v1/ali_qrcode.api';
        } elseif ($payType == 31) {
            $this->getwayUrl .= 'api/v1/qq_qrcode.api';
        } elseif ($payType == 41) {
            $this->getwayUrl .= 'api/v1/jd_qrcode.api';
        } elseif ($payType == 11) {
            $this->getwayUrl .= 'api/v1/wx_h5.api';
        } elseif ($payType == 22) {
            $this->getwayUrl .= 'api/v1/ali_h5.api';
        } else {
            $this->getwayUrl .= 'api/v1/union.api';
        }

        $this->params = [
            'mch_id'       => $this->mch_id,
            'sign_type'    => $this->sign_type,
            'mch_order'    => $this->orderNo,
            'amt'          => $this->amt, // 厘
            'remark'       => $this->remark,
            'created_at'   => time(),
            'client_ip'    => Utils::getIp(),
            'notify_url'   => $this->notify_url,
            'pay_type'     => $payType,
            'callback_url' => $this->callback_url,
            'mch_key'      => $this->mch_key,
            'call'         => "{user:1}"
        ];
        if (!in_array($this->params['pay_type'], [10, 20, 31, 41, 2, 11, 22])) {
            $this->params['bank_code'] = $payType;
            $this->params['pay_type']  = '3';
        }
        ksort($this->params);

        // $this->params['sign'] = md5($this->getSignStr($this->params));

        // $sign = md5(urldecode(http_build_query($this->params)));

        $this->params['sign'] = $this->getSignStr($this->params);

        unset($this->params['mch_key']);

        // echo $this->params['sign'] . "<hr/>";

        // echo $sign . "<hr/>";exit();

        // print_r($this->params);exit;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $client = new Client();
        // echo $this->params['pay_type'];die;
        if (!in_array($this->params['pay_type'], [10, 20, 31, 41, 2, 11, 22])) {
            // echo $this->getwayUrl . "<hr/>";
            return $this->htmlSend($this->getwayUrl, $this->params);
        } else {
            // echo $this->getwayUrl . "<hr/>";
            // print_r($this->params);die();
            $res = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);

            if ($res->getStatusCode() == '200') {
                $returnStr = $res->getBody()->getContents();
                if (Utils::isJSON($returnStr)) {
                    $resArr = json_decode($returnStr, true);
            
                    // file_put_contents('C:\wwwroot\pay\src\1.txt', var_export($resArr,ture));
                    $code   = $resArr['code'] ?? '';
                    $msg    = $resArr['msg'] ?? '';
                    if ($code !== '' && $code == '1') {
                        $payQRCode    = $resArr['code_url'] ?? '';
                        $payQRCodeUrl = $resArr['redirect_pay_url'] ?? '';
                        $qrcodeUrl    = Utils::getQrcode($payQRCode);
                        if (in_array($this->params['pay_type'], [11, 22])) {
                            return $payQRCodeUrl;
                        }
                        return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->amt / 1000) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
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

    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign   = $params['sign'];
        $mySign = $this->getSignStr($params);

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
     * html表单提交
     * @param  string $url    网关地址
     * @param  array  $params 提交参数
     * @param  string $type   提交方法
     * @return [type]         [description]
     */
    private function htmlSend($url = '', $params = [], $type = 'post')
    {
        $tmp = '<html>';
        $tmp .= '<head>';
        $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
        $tmp .= '<title>Pay Page</title>';
        $tmp .= '</head>';
        $tmp .= '<body>'; //  style="display:none;"
        $tmp .= '<form action="' . $url . '" method="' . $type . '" name="orderForm">';
        foreach ($params as $key => $value) {
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
     * getSignStr 签名生成
     */
    private function getSignStr($parter)
    {
        if (isset($parter['sign'])) {
            $sign = "orderid={$parter['orderid']}&opstate={$parter['opstate']}&ovalue={$parter['ovalue']}";
        } else {
            $sign = md5(urldecode(http_build_query($parter)));
            unset($parter['mch_key']);
            $parter['sign'] = $sign;
        }

        return $sign;
    }

}
