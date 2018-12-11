<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;
//24
class SingleQrPay implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
        ],
        2 => [
            '7' => '支付宝',
        ],
        3 => [
        ],
        4 => [
        ],
        5 => [
        ],
        6 => [
        ],
        7 => [
        ],
        8 => [//微信WAP
        ],
        9 => [//支付宝WAP
            '4' => '支付宝WAP',
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

    //支付网关
    public $getwayUrl = 'http://api.mashangshouqian.cn/passivePay.jhtml';
    //WAP网关
    public $wapUrl    = 'http://api.mashangshouqian.cn/wapPay.jhtml';

    //请求网关
    public $requestUrl;

    //银行扫码公用参数
    public $merchno;            //商户号 15  是   是   商户签约时，本系统分配给商家的唯一标识。
    public $parterKey;          //商户key
    public $amount;             //交易金额    12  是   是   以元为单位
    public $traceno;            //商户流水号   32  是   是   商户网站唯一订单号，由商户系统生成，保证其唯一性。
    public $payType = '7';      //支付方式    1   是   是   7 - 支付宝个人码
    public $goodsName;          //商品名称    128 否   是   默认取商户名称
    public $notifyUrl;          //通知地址    150 是   是   交易成功，则给商户发送异步通知。
    public $remark;             //备注  50  否   是   商户网站自定义，系统原样返回给商户网站。
    public $signature;          //数据签名    32  是       对签名数据进行MD5加密的结果。
    /**
     * 提交参数
     * @var array
     */
    public $params = [];


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
            $this->merchno             = $conf['parterNo'];
            $this->parterKey           = $conf['parterKey'];
            $this->notifyUrl           = $conf['callbackUrl'];
            //$this->notify_url          = $conf['notifyUrl'];
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
        //$money                  = sprintf("%.2f", $money);
        $this->amount           = $money;
        $this->traceno          = $orderId;
        $this->payType          = $payType;

        $this->params = [
            'merchno'    => $this->merchno,            //商户号 15  是   是   商户签约时，本系统分配给商家的唯一标识。
            'amount'     => (int)$this->amount,             //交易金额    12  是   是   以元为单位
            'traceno'    => $this->traceno,            //商户流水号   32  是   是   商户网站唯一订单号，由商户系统生成，保证其唯一性。
            'payType'    => $this->payType,            //支付方式    1   是   是   7 - 支付宝个人码
            'notifyUrl'  => $this->notifyUrl,          //通知地址    150 是   是   交易成功，则给商户发送异步通知。
        ];
        //排序&签名
        ksort($this->params);
        $signStr = urldecode(http_build_query($this->params)) .'&'. $this->parterKey;
        $this->params['signature'] = strtoupper(md5($signStr));

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        //if ($this->params['payType'] == '4') {
            /*$tmp = '<html>';
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
            $tmp .= '<script type="text/javascript" src="/js/jquery-1.11.0.min.js"></script>';
            $tmp .= '<script type="text/javascript">';
            //$tmp .= 'document.orderForm.submit();';
            //$tmp .= '</script>'; var submit_sync =
            $tmp .= '(function() {
                    $.ajax({
                        type: "post",
                        url: "'. $this->getwayUrl .'",
                        async: false,
                        data: JSON.stringify({';
                        foreach ($this->params as $key => $value) {
                            $tmp .= $key . ': $('.'\'input[name="'. $key .'"]\''.').val(),'.PHP_EOL;
                        }
            $tmp .= 'now: new Date().getTime()
                        }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        success: function(data1) {
                            //$("#result").text(data.result);
                            document.write(data1);
                        }
                    });
                })()';
            $tmp .= '</script>';
            $tmp .= '</body>';
            $tmp .= '</html>';

            $data = json_encode($this->params);
            $opts = [
                'http' => [
                'method' => 'POST',
                'header'=> "Content-type:application/json" . PHP_EOL . "Content-Length: " . strlen($data) . PHP_EOL,
                'content' => $data
                ]
            ];

            $context = stream_context_create($opts);
            $html = file_get_contents($this->getwayUrl, false, $context);

            return $html;*/
        //} else {
        if ($this->params['payType'] == '4') {
            $result = Utils::sendPostMore($this->wapUrl, json_encode($this->params), 'json', 120, true);   //return json_encode($this->params).'******'.$this->wapUrl.'********'.$result;
        } else {
            $result = Utils::sendPostMore($this->getwayUrl, json_encode($this->params), 'json', 120, true);   //return json_encode($this->params).'******'.$this->getwayUrl.'********'.$result;
        }

            if ($result) {
                $resArr = json_decode($result, true);
                if ($resArr && $resArr['respCode'] == '10') {
                    $qrcode   = $resArr['barCode'] ?? '';
                    unset($resArr['barCode']);

                    $state = $this->verifySign($resArr);
                    if (!$state || empty($qrcode)) {
                        return false;
                    }
                    if ($this->params['payType'] == '4') {
                        return $qrcode;
                    }
                    $qrcodeUrl = Utils::getQrcode($qrcode);
                    return 'https://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->traceno . '&fee=' . sprintf("%.2f", $this->amount) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);

                }
            }else{
                return false;
            }
        //}
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        if (isset($params['signature'])) {
            $sign = $params['signature'];
            unset($params['signature']);

            ksort($params);
            $signStr = urldecode(http_build_query($params)) .'&'. $this->parterKey;
            $mySign = strtoupper(md5($signStr));

            if ($sign == $mySign) {
                return true;
            } else {
               return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

}