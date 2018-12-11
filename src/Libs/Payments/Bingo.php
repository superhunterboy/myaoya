<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Bingo implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1  => [ //微信扫码
            '0101' => '微信扫码',
        ],
        2  => [ //支付宝扫码
            '02010' => '支付宝扫码',
        ],
        3  => [ //网银
            '102' => '工商银行',
            '103' => '农业银行',
            '104' => '中国银行',
            '105' => '建设银行',
            '302' => '中信银行',
            '303' => '光大银行',
            '304' => '华夏银行',
            '306' => '广发银行',
            '308' => '招商银行',
            '309' => '兴业银行',
            '310' => '浦发银行',
            '403' => '邮储银行',
        ],
        4  => [ //QQ扫码
            '0501' => 'QQ钱包扫码',
        ],
        5  => [ //京东扫码
            '0601' => '京东钱包扫码',
        ],
        6  => [ //百度扫码
        ],
        7  => [ //银联扫码
            '0701' => '银联二维码',
        ],
        8  => [ //微信WAP
            '01030' => '微信H5',
        ],
        9  => [ //支付宝WAP
            '0203' => '支付宝H5',
        ],
        10 => [ //QQWAP
            '0503' => 'QQ钱包H5',
        ],
        11 => [ //京东WAP
            '0603' => '京东钱包H5',
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
    public $getwayUrl = 'https://www.bingopay.net/open-gateway/trade/invoke';

    /**
     * 商户编号
     * @var string
     */
    public $orgId;
    public $merno;

    /**
     * 商户key
     * @var string
     */
    public $md5Key;
    public $desKey;

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
     * 提交参数
     * @var array
     */
    public $params = [];
    public $orderNo;
    public $money;
    public $payType;

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
            $this->orgId = $conf['parterNo'];
            $keyArr      = json_decode($conf['parterKey'], true); // {"merno":"...","md5Key":"...","desKey":"..."}
            if (is_array($keyArr)) {
                $this->merno  = $keyArr['merno'];
                $this->md5Key = $keyArr['md5Key'];
                $this->desKey = $keyArr['desKey'];
            }
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
        $this->orderNo = $orderId;
        $this->money   = $money * 100; // 分
        $this->payType = $payType;

        if (in_array($payType, ['0101', '02010'])) {
            $this->market = true;
        }

        if (in_array($payType, ['0101', '02010', '0501', '0601', '0701', '01030', '0203', '0503', '0603'])) {
            $businessData = [
                'merno'      => $this->merno,
                'bus_no'     => substr($payType, 0, 4),
                'amount'     => $this->money,
                'goods_info' => 'CZ',
                'order_id'   => $this->orderNo,
                'return_url' => $this->notifyUrl,
                'notify_url' => $this->callbackUrl,
            ];
        } else {
            $payTypes = $this->getPayType();
            $cardname = $payTypes[3][$payType];

            $businessData = [
                'merno'      => $this->merno,
                'bus_no'     => '0499',
                'amount'     => $this->money,
                'goods_info' => 'CZ',
                'order_id'   => $this->orderNo,
                'cardname'   => $cardname,
                'bank_code'  => $payType,
                'cardno'     => '', // 银行卡号
                'idcard'     => '', // 身份证
                'name'       => '', // 姓名
                'phone'      => '', // 手机
                'return_url' => $this->notifyUrl,
                'notify_url' => $this->callbackUrl,
                'card_type'  => 1, // 1储蓄卡 2 信用卡
                'validate'   => '', // 信用卡有效期
                'cvv2'       => '', // 信用卡cvv2
                'channelid'  => 1,
            ];
        }

        // print_r($businessData);exit();

        $this->params = [
            'requestId'    => $this->orderNo,
            'orgId'        => $this->orgId,
            'timestamp'    => date('YmdHis'),
            'productId'    => in_array($payType, ['0101', '02010', '0501', '0601', '0701', '01030', '0203', '0503', '0603']) ? '0100' : '0500',
            'businessData' => urlencode(Utils::des_ecb_encrypt(json_encode($businessData), $this->desKey)),
            'dataSignType' => 1,
        ];

        $temp = $this->getSignStr($this->params);

        $this->params['signData'] = strtoupper(md5($temp . $this->md5Key));

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        // print_r($this->params);exit();
        $client = new Client();
        $res    = $client->request('POST', $this->getwayUrl, ['form_params' => $this->params]);
        if ($res->getStatusCode() == '200') {
            $resData = '';
            $body    = $res->getBody();
            while (!$body->eof()) {
                $resData .= $body->read(1024);
            }
            $resArr = json_decode($resData, true);
            // {
            //     "key":"05",
            //     "msg":"获取成功",
            //     "requestId":"201806261117415934624",
            //     "respCode":"00",
            //     "respMsg":"通讯成功",
            //     "result":"{\"ishtml\":\"0\",\"url\":\"http://47.91.212.244:18888/open-gateway/redirect/go?_t=1529983064269174421\"}",
            //     "status":"3"
            // }
            if ($resArr && isset($resArr['respCode']) && $resArr['respCode'] == '00') {
                if (isset($resArr['result']) && $resArr['result']) {
                    $resultArr = json_decode($resArr['result'], true);
                    if (isset($resultArr['url']) && $resultArr['url']) {
                        // 扫码支付的，返回的是一个二维码内容
                        if (in_array($this->payType, ['0101', '02010', '0501', '0601', '0701'])) {
                            $payQRCode = $resultArr['url'] ?? '';
                            $qrcodeUrl = Utils::getQrcode($payQRCode);
                            return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->orderNo . '&fee=' . sprintf("%.2f", $this->money / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($payQRCode);
                        } else {
                            return $resultArr['url'];
                        }
                    }
                }
            }
            if (isset($this->market)) {
                return json_encode(array_merge(['order' => $this->orderNo], $resArr), 320);
            } else {
                return json_encode(['code' => $resArr['respCode'], 'error' => $resArr['msg']], JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        $sign = strtoupper($params['sign_data']);
        unset($params['sign_data']);
        $verifySign = strtoupper(md5($this->getSignStr($params) . $this->md5Key));
        return $verifySign === $sign;
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

    private function getSignStr($params = [])
    {
        ksort($params);
        $temp = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $temp .= $key . '=' . $value . '&';
            }
        }
        return substr($temp, 0, strlen($temp) - 1);
    }
}
