<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;
use \Weiming\Models\Pay;

class VPay implements WMPay
{
    /**
     * 支付类型
     */

    public $paymentType = [
        1  => [ //微信扫码
        ],
        2  => [ //支付宝扫码
            'VPay_ZFB'=>'支付宝扫码'
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
    public $QRCodeUrl;

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
    public $pubKey;
    public $priKey;


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
    public function signature($payType, $money, $newOrder)
    {
        $bankType='002';
        $cardNo='6230520760011027478';
        $bankCode='ABC';
        $accountName='肖耀伟';
        $returnType='image';

        $bankMap=['CCB'=>'001','ABC'=>'002','ICBC'=>'003','BOC'=>'004','CMBC'=>'005',
                  'CMB'=>'006','CIB'=>'007','BCM' =>'008','CEB'=>'009','GDB' =>'010'];
        $config  = require __DIR__ . '/../../../config/db.php';
        try{
            $url = $config['api_bankcard'];
            $client = new Client();
            $response = $client->request('GET', $url);
            if ($response->getStatusCode() == 200)
            {
                $sBankcard = $response->getBody()->getContents();
                $oBankcard = json_decode($sBankcard);
                $bankType  = $bankMap[$oBankcard->bankcode];
                $cardNo    = $oBankcard->card_number;
                $bankCode  = $oBankcard->bankcode;
                $accountName = $oBankcard->account;
            }
            else
            {
                return json_encode(['code'=>1000, 'error'=>'内部服务器错误']);
            }
        }
        catch (Exception $e)
        {
            //http请求失败或超时
            return json_encode(['code'=>1000, 'error'=>'银行卡信息获取失败']);
        }

        //如用户输入100元 二维码生成金额范围是 99.00,99.01,99.02...99.99,100.00
        $QRCodeMoney = Utils::randMoney($money); //金额（单位：分）

        //订单号格式 20190126-ABC-9736-9958
        $order_no = date('Ymd').'-'.$bankCode.'-'.substr($cardNo, -4).'-'.$QRCodeMoney;
        $orderNoExists = Pay::where('order_no', $order_no)->exists();
        if($orderNoExists)  //如果订单号存在则重新再生成一次
        {
            $QRCodeMoney = Utils::randMoney($money,2); //金额（单位：分）
            $order_no = date('Ymd').'-'.$bankCode.'-'.substr($cardNo, -4).'-'.$QRCodeMoney;
        }
        try{
            $newOrder->order_no = $order_no;
            $newOrder->save();
        }
        catch(Exception $e)
        {
            return json_encode(['code'=>1000, 'error'=>'订单生成失败，请刷新重试']);
        }
        $this->money = number_format(($QRCodeMoney / 100), 2, '.', '');
        $this->QRCodeUrl=$config['api_AliPayQRCode'].'?cardNo='.$cardNo.'&accountName='.$accountName.'&bankType='.$bankType.'&money='.$QRCodeMoney.'&returnType='.$returnType;

        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html>';
        $html .= '<head lang="en">';
        $html .= '<meta charset="utf-8">';
        $html .= '<meta content="width=device-width,initial-scale=1.0, user-scalable=no" name="viewport" />';
        $html .= '<meta content="yes" name="apple-mobile-web-app-capable" />';
        $html .= '<meta content="no" name="apple-touch-fullscreen" />';
        $html .= '<meta content="black" name="apple-mobile-web-app-status-bar-style" />';
        $html .= '<meta content="telephone=no" name="format-detection"/>';
        $html .= '<title>支付宝扫码支付</title>';
        $html .= '<style>';
        $html .= 'body{text-align:center;margin-top:60px;}';
        $html .= '.fee{color:red;font-size:20px;}';
        $html .= '</style>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div class="info">';
        $html .= '<div><a href="/" style="color:#000;">返回快捷充值中心</a></div>';
        $html .= '<h3>支付宝扫码支付</h3>';
        $html .= '<div class="order">支付金额：<span class="fee">' . $this->money . ' 元</span></div>';
        $html .= '</div>';
        $html .= '<div class="qrcode">';
        $html .= '<img src=' . $this->QRCodeUrl . ' width="300" height="300"/>';
        $html .= '</div>';
        $html .= '<div>提示：请使用支付宝扫描二维码以完成支付</div>';
        $html .= '<div class="fee">温馨提示：请勿重复支付 、重复支付无效</div>';
        $html .= '</body>';
        $html .= '</html>';
        return $html;
    }


    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {

    }

    /**
     * 查询支付
     */
    public function query($orderId)
    {
    }

}
