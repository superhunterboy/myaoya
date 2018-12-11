<?php

namespace Weiming\Libs\Payments;

use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

//46
class Zhongxin implements WMPay
{
    public $paymentType = [
        1 => [
            'weixin' => '微信',
        ],
        2 => [
            'alipay' => '支付宝',
        ],
        3 => [
            'ICBC'  => '工商银行',
            'ABC'   => '农业银行',
            'BOCSH' => '中国银行',
            'CCB'   => '建设银行',
            'CMB'   => '招商银行',
            'SPDB'  => '浦发银行',
            'GDB'   => '广发银行',
            'BOCOM' => '交通银行',
            'PSBC'  => '邮政银行',
            'CNCB'  => '中信银行',
            'CMBC'  => '民生银行',
            'CEB'   => '光大银行',
            'HXB'   => '华夏银行',
            'CIB'   => '兴业银行',
            'BOS'   => '上海银行',
            'SRCB'  => '上海农商',
            'PAB'   => '平安银行',
            'BCCB'  => '北京银行',
        ],
        4 => [
            'qqQR' => 'QQ钱包',
        ],
        5  => [
        ],
        6  => [
        ],
        7  => [
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
     */
    public $getwayUrl = 'http://pay.zxshop.top/checkout';
    public $apiwayUrl = 'http://pay.zxshop.top/apisubmit';

    public $version = '1.0';    //版本号     默认1.0
    public $customerid; //商户编号     商户后台获取
    public $parterKey;  //商户key
    public $sdorderno;  //商户订单号
    public $total_fee;  //订单金额      精确到小数点后两位，例如10.24
    public $paytype;    //支付编号   详见附录1
    public $bankcode;   //银行编号   网银直连不可为空，其他支付方式可为空  详见附录2
    public $notifyurl;  //异步通知URL     不能带有任何参数
    public $returnurl;  //同步跳转URL     不能带有任何参数
    public $remark;     //订单备注说明 Y   可为空
    public $get_code;   //获取微信二维码    Y   如果只想获取被扫二维码，请设置get_code=1
    public $sign;       //md5签名串     参照md5签名说明

    //初始化提交用数据数组
    public $params = [];

    //静态实例对象
    private static $instance;

    public static function getInstance($conf = [])
    {

        if (!(static::$instance instanceof static )) {

            static::$instance = new static($conf);

        }

        return static::$instance;
    }

    //对象初始化
    private function __construct($conf)
    {

        if ($conf) {

            $this->customerid   = $conf['parterNo'];
            $this->parterKey    = $conf['parterKey'];
            $this->notifyurl    = $conf['callbackUrl'];
            $this->returnurl    = $conf['notifyUrl'];
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
        $money = sprintf("%.2f", $money);
        if (in_array($paytype, ['qqQR', 'alipay', 'weixin'])) {
            $this->paytype     = $payType;
        } else {
            $this->paytype     = 'bank';
            $this->bankcode    = $payType;
        }
        $this->sdorderno       = $orderId;
        $this->total_fee       = $money;

        $this->params = [
            'version'       => $this->version,    //版本号     默认1.0
            'customerid'    => $this->customerid, //商户编号     商户后台获取
            'parterKey'     => $this->parterKey,  //商户key
            'sdorderno'     => $this->sdorderno,  //商户订单号
            'total_fee'     => $this->total_fee,  //订单金额      精确到小数点后两位，例如10.24
            'paytype'       => $this->paytype,    //支付编号   详见附录1
            'bankcode'      => $this->bankcode,   //银行编号   网银直连不可为空，其他支付方式可为空  详见附录2
            'notifyurl'     => $this->notifyurl,  //异步通知URL     不能带有任何参数
            'returnurl'     => $this->returnurl,  //同步跳转URL     不能带有任何参数
        ];
        //签名
        $signStr = 'version='.$this->version.'&customerid='.$this->customerid.'&total_fee='.$this->total_fee.'&sdorderno='.$this->sdorderno.'&notifyurl='.$this->notifyurl.'&returnurl='.$this->returnurl.'&'.$this->parterKey;
        $this->params['sign'] = md5($signStr);

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
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
    public function verifySign($data = [])
    {
        $status     =$data['status'] ?? '';
        $customerid =$data['customerid'] ?? '';
        $sdorderno  =$data['sdorderno'] ?? '';
        $total_fee  =$data['total_fee'] ?? '';
        $paytype    =$data['paytype'] ?? '';
        $sdpayno    =$data['sdpayno'] ?? '';
        $remark     =$data['remark'] ?? '';
        $sign       =$data['sign'] ?? '';

        $mysign=md5('customerid='.$customerid.'&status='.$status.'&sdpayno='.$sdpayno.'&sdorderno='.$sdorderno.'&total_fee='.$total_fee.'&paytype='.$paytype.'&'.$this->parterKey);

        if($sign == $mysign){
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询支付
     *$orderId    代付订单号
     **/

    public function query($orderId)
    {

        //时间参数
        $time = date('YmdHis');

        //电子签名
        $info = md5("{$this->merId}&{$time}&{$orderId}&{$this->merKey}");

        //组成提交数据
        $data = [
            'Version'   => '2.1',
            'SignType'  => $this->signType,
            'SignInfo'  => $info,
            'Charset'   => 'UTF-8',
            'TimeStamp' => $time,
            'MerId'     => $this->merId,
            'OrdId'     => $orderId,
        ];

        $tmp = '<html>';
        $tmp .= '<head>';
        $tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
        $tmp .= '<title>Pay Page</title>';
        $tmp .= '</head>';
        $tmp .= '<body style="display:none;">';
        $tmp .= '<form action="' . $this->getwayUrl . '" method="post" name="orderForm">';
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
