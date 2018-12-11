<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Weiming\Libs\Payments\WMPay;
use Weiming\Libs\Utils;

class Tianfubao implements WMPay
{
    /**
     * 支付类型
     */
    public $paymentType = [
        1 => [
            'wxpay' => '微信',
        ],
        2 => [
            'alipay' => '支付宝',
        ],
        3 => [
            '1000' => '银联通道',
            '1001' => '工商银行',
            '1002' => '农业银行',
            '1003' => '中国银行',
            '1004' => '建设银行',
            '1005' => '交通银行',
            '1006' => '邮政储蓄银行',
            '1007' => '中信银行',
            '1008' => '光大银行',
            '1009' => '华夏银行',
            '1010' => '民生银行',
            '1011' => '平安银行',
            '1012' => '招商银行',
            '1013' => '兴业银行',
            '1014' => '浦发银行',
            '1016' => '北京银行',
            '1017' => '广发银行',
            '1025' => '上海银行',
            '1103' => '北京农村商业银行',
        ],
        4 => [
            'qqpay' => 'QQ钱包',
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
     * @var string
     */
    public $getwayUrl = [
        'wxpay'   => 'http://upay.tfb8.com/cgi-bin/v2.0/api_wx_pay_apply.cgi',
        'alipay'  => 'http://upay.tfb8.com/cgi-bin/v2.0/api_ali_pay_apply.cgi',
        'netbank' => 'http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi',
        // 'wxpay'   => 'http://apitest.tfb8.com/cgi-bin/v2.0/api_wx_pay_apply.cgi',
        // 'alipay'  => 'http://apitest.tfb8.com/cgi-bin/v2.0/api_ali_pay_apply.cgi',
        // 'netbank' => 'http://apitest.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi',
    ];

    /**
     * 商户key
     * @var string
     */
    public $parterKey;

    /**
     * 回调地址
     * @var string
     */
    public $return_url;

    /**
     * 通知地址
     * @var string
     */
    public $notify_url;

    /**
     * 提交参数
     * @var array
     */
    public $params = [];

    /**
     * 微信、支付宝共用字段，不参与签名部分
     * @var string
     */
    public $sign_type      = "MD5";
    public $ver            = "1";
    public $input_charset  = "UTF-8";
    public $sign_key_index = "1";
    public $sign;

    /**
     * 商户编号
     * @var string
     */
    public $spid; // 微信、支付宝、网银共用
    public $cur_type = "CNY"; // 微信、支付宝为 CNY，网银为 1

    /**
     * 支付宝、微信共用字段，参与签名部分
     * @var string
     */
    public $sp_billno;
    public $spbill_create_ip;
    public $pay_type = "800201";
    public $tran_time;
    public $tran_amt;
    public $auth_code;
    public $item_name   = "充值";
    public $item_attach = "";

    /**
     * 微信字段，参与签名部分
     * @var string
     */
    public $pay_show_url;
    public $out_channel = "wxpay"; // wxpay、qqpay，默认 wxpay
    public $pay_limit;
    public $bank_mch_name = "201709102718";
    public $bank_mch_id   = "2721222";
    public $sp_udid;

    /**
     * 网银字段，参与签名部分
     */
    public $spbillno;
    public $money;
    public $errpage_url;
    public $memo = '充值';
    public $expire_time;
    public $attach       = ''; // 附加字段
    public $card_type    = '1'; // 1 借记卡 2 贷记卡
    public $bank_segment = '6666';
    public $user_type    = '1'; // 1 个人 2 企业
    public $channel      = '1'; // 1 PC 2 手机
    public $encode_type  = 'MD5';
    public $risk_ctrl    = '';

    private $sp_userid = '212458724'; // 网银、自定义
    private $pubkey;
    private $prikey;
    private $md5key;

    private $requestUrl;
    private $isNetBank = false;

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
            $this->spid         = $conf['parterNo'];
            $this->parterKey    = $conf['parterKey']; // {"uid":"","pubkey":"","prikey":"","md5key":""}
            $this->return_url   = $conf['callbackUrl']; // 网银
            $this->pay_show_url = $conf['callbackUrl']; // 微信
            $this->notify_url   = $conf['notifyUrl']; // 微信、支付宝、网银
            if ($this->parterKey) {
                $parterKey    = json_decode($this->parterKey, true);
                $this->pubkey = $parterKey['pubkey'];
                $this->prikey = $parterKey['prikey'];
                $this->md5key = $parterKey['md5key'];
            }
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
        $money                  = $money * 100;
        $this->money            = $money;
        $this->tran_amt         = $money;
        $this->sp_billno        = $orderId;
        $this->spbillno         = $orderId;
        $this->item_name        = $orderId;
        $this->memo             = $orderId;
        $this->tran_time        = date('YmdHis');
        $this->spbill_create_ip = $_SERVER['SERVER_ADDR'];
        $this->bank_segment     = $payType;
        if (!in_array($payType, ['wxpay', 'alipay', 'qqpay'])) {
            // 网银
            $this->cur_type         = 1;
            $params['spid']         = $this->spid;
            $params['sp_userid']    = $this->sp_userid;
            $params['spbillno']     = $this->spbillno;
            $params['money']        = $this->money;
            $params['cur_type']     = $this->cur_type;
            $params['notify_url']   = $this->notify_url;
            $params['return_url']   = $this->return_url;
            $params['errpage_url']  = $this->errpage_url;
            $params['memo']         = $this->memo;
            $params['expire_time']  = $this->expire_time;
            $params['attach']       = $this->attach;
            $params['card_type']    = $this->card_type;
            $params['bank_segment'] = $this->bank_segment;
            $params['user_type']    = $this->user_type;
            $params['channel']      = $this->channel;
            $params['encode_type']  = $this->encode_type;
            $params['risk_ctrl']    = $this->risk_ctrl;
        } elseif (in_array($payType, ['wxpay', 'qqpay'])) {
            // 微信、QQ钱包
            $params['spid']             = $this->spid;
            $params['notify_url']       = $this->notify_url;
            $params['pay_show_url']     = $this->pay_show_url;
            $params['sp_billno']        = $this->sp_billno;
            $params['spbill_create_ip'] = $this->spbill_create_ip;
            $params['out_channel']      = ($payType == "wxpay" ? 'wxpay' : 'qqpay');
            $params['pay_type']         = $this->pay_type;
            $params['tran_time']        = $this->tran_time;
            $params['tran_amt']         = $this->tran_amt;
            $params['cur_type']         = $this->cur_type;
            $params['pay_limit']        = $this->pay_limit;
            $params['auth_code']        = $this->auth_code;
            $params['item_name']        = $this->item_name;
            $params['item_attach']      = $this->item_attach;
            $params['bank_mch_name']    = $this->bank_mch_name;
            $params['bank_mch_id']      = $this->bank_mch_id;
            $params['sp_udid']          = $this->sp_udid;
        } elseif ($payType == 'alipay') {
            // 支付宝
            $params['spid']             = $this->spid;
            $params['notify_url']       = $this->notify_url;
            $params['sp_billno']        = $this->sp_billno;
            $params['spbill_create_ip'] = $this->spbill_create_ip;
            $params['pay_type']         = $this->pay_type;
            $params['tran_time']        = $this->tran_time;
            $params['tran_amt']         = $this->tran_amt;
            $params['cur_type']         = $this->cur_type;
            $params['auth_code']        = $this->auth_code;
            $params['item_name']        = $this->item_name;
            $params['item_attach']      = $this->item_attach;
        }

        if (in_array($payType, ['wxpay', 'alipay', 'qqpay'])) {
            $this->sign = strtoupper(md5(Utils::getSignStr($params) . 'key=' . $this->md5key));
            // 不参与验签
            $params["sign_type"]      = $this->sign_type;
            $params["ver"]            = $this->ver;
            $params["input_charset"]  = $this->input_charset;
            $params["sign_key_index"] = $this->sign_key_index;
            $this->requestUrl         = ($payType == 'alipay' ? $this->getwayUrl['alipay'] : $this->getwayUrl['wxpay']) . '?' . Utils::getSignStr($params) . 'sign=' . $this->sign;
        } else {
            $this->isNetBank  = true;
            $signStr          = Utils::getSignStr($params);
            $plaintext        = $signStr . 'sign=' . strtolower(md5($signStr . 'key=' . $this->md5key));
            $this->requestUrl = $this->getwayUrl['netbank'] . "?cipher_data=" . urlencode(Utils::tfbEnCrypt($plaintext, $this->pubkey));
        }
        return $this;
    }

    /**
     * 支付请求
     */
    public function payment()
    {
        if ($this->isNetBank) {
            return $this->requestUrl;
        } else {
            $client = new Client();
            $res    = $client->request('GET', $this->requestUrl);
            if ($res->getStatusCode() == '200') {
                $resData = $res->getBody();
                if ($resData) {
                    $returnArr = Utils::toArray($resData);
                    /**
                     *   <root>
                     *   <cur_type>CNY</cur_type>
                     *   <listid>1021800776625170906000077606</listid>
                     *   <merch_listid>2017090608415260</merch_listid>
                     *   <pay_type>800201</pay_type>
                     *   <qrcode>weixin://wxpay/bizpayurl?pr=9Kyx9Ms</qrcode>
                     *   <retcode>00</retcode>
                     *   <retmsg>操作成功</retmsg>
                     *   <sign>3ffe15f92499afa63986efa710db6051</sign>
                     *   <sp_billno>20170906190251</sp_billno>
                     *   <spid>1800776625</spid>
                     *   <sysd_time>20170906190345</sysd_time>
                     *   <tran_amt>1</tran_amt>
                     *   </root>
                     *
                     *   <root>
                     *   <cur_type>CNY</cur_type>
                     *   <listid>1021800776625170906000077609</listid>
                     *   <pay_type>800201</pay_type>
                     *   <qrcode>https://qr.alipay.com/bax04786p3gonuokws6h4057</qrcode>
                     *   <retcode>00</retcode>
                     *   <retmsg>操作成功</retmsg>
                     *   <sign>6890f243947ecca9490aefb5957e7daa</sign>
                     *   <sp_billno>20170906191153</sp_billno>
                     *   <spid>1800776625</spid>
                     *   <sysd_time>20170906191247</sysd_time>
                     *   <tran_amt>1</tran_amt>
                     *   </root>
                     *
                     *   Array
                     *   (
                     *       [cur_type] => CNY
                     *       [listid] => 1021800776625170909000078337
                     *       [pay_type] => 800201
                     *       [qrcode] => https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5V60bcfc9cff035193e101337fc939e4
                     *       [retcode] => 00
                     *       [retmsg] => 操作成功
                     *       [sign] => fe8086bbb83274f3b1e40b71e8716ca2
                     *       [sp_billno] => 201709091549031145002
                     *       [spid] => 1800776625
                     *       [sysd_time] => 20170909155003
                     *       [tran_amt] => 1
                     *   )
                     */
                    $qrcode   = $returnArr['qrcode'] ?? '';
                    if (preg_match("/tenpay/", $qrcode)) {
                        $returnArr['qrcode'] = '<![CDATA[' . $qrcode . ']]>';
                    }
                    if (Utils::isTianfubaoSign($returnArr, $this->md5key) && isset($returnArr['retcode']) && $returnArr['retcode'] == '00') {
                        $qrcodeUrl = Utils::getQrcode($qrcode);
                        return 'http://' . $_SERVER['HTTP_HOST'] . '/payment/scancode?trade_no=' . $this->sp_billno . '&fee=' . sprintf("%.2f", $this->tran_amt / 100) . '&qrcode=' . urlencode($qrcodeUrl) . '&codeurl=' . urlencode($qrcode);
                    } else {
                        return json_encode(['order' => $this->sp_billno]);
                    }
                }
            }
        }
    }

    /**
     * callback回调验证
     */
    public function verifySign($params = [])
    {
        /**
         *   网银
         *   {
         *       "cur_type": "1",
         *       "encode_type": "RSA",
         *       "listid": "1021800071515170908000078245",
         *       "money": "1000",
         *       "pay_type": "2",
         *       "result": "1",
         *       "spbillno": "201709081827147732811",
         *       "spid": "1800071515",
         *       "user_type": "1",
         *       "sign": "h0pAA7a6NCz2ksWK6WXp9tHgCEunf9Z8txQ3MY6GxpgIge8vhCOpriHiqmIXtD019zxFbYWjF7k1lT3fo 7KFodxY n/URjkQNrT3G/X/EtkFD6nH9hWPsAHJ19jiH3FSGy/wEOqSmMUCd5 2aSSH9sGuUwtyyniLRo1N55e pI=",
         *       "retcode": "00",
         *       "retmsg": "操作成功"
         *   }
         *
         *   支付宝、微信、QQ钱包
         *   array (
         *     'item_attach' => '',
         *     'item_name' => '',
         *     'listid' => '1021800776625170906000077606',
         *     'notify_type' => '1',
         *     'pay_type' => '800201',
         *     'retcode' => '00',
         *     'retmsg' => '',
         *     'sign' => 'bd062a5625f8ed71978e065f3dc2df17',
         *     'sign_type' => 'MD5',
         *     'sp_billno' => '20170906190251',
         *     'sysd_time' => '20170906190506',
         *     'tran_amt' => '1',
         *     'tran_state' => '1',
         *     'tran_time' => '20170906190344',
         *   )
         */
        if (isset($params['spbillno']) && $params['spbillno']) {
            if ($params['encode_type'] == 'RSA') {
                return Utils::isTianfubaoRsaVerifySign(Utils::isTianfubaoVerifyStr($params), $this->pubkey, str_replace(' ', '+', $params['sign']));
            }
            return false;
        } elseif (isset($params['sp_billno']) && $params['sp_billno']) {
            if ($params['sign_type'] == 'MD5') {
                return Utils::isTianfubaoVeritfySign($params, $this->md5key);
            }
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
