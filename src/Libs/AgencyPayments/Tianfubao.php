<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Tianfubao
{
    private $gateway         = 'http://api.tfb8.com/cgi-bin/v2.0/api_pay_single.cgi';
    private $queryUrl        = 'http://api.tfb8.com/cgi-bin/v2.0/api_pay_single_query.cgi';
    private $balanceQueryUrl = 'http://api.tfb8.com/cgi-bin/v2.0/api_pay_balance_query.cgi';
    private $version         = '1.0';
    private $spid; // 必须，商户号
    private $sp_serialno; // 必须，代付单号
    private $sp_reqtime; // 必须，请求时间，格式：年月日时分秒
    // private $tran_amt; // 必须，金额，单位：分
    private $cur_type = '1'; // 必须，金额类型，1 人民币，单位： 分
    private $pay_type = '1'; // 付款方式，1 余额 2 企业网银 3 垫资支付，默认为 1
    // private $acct_name; // 必须，收款人姓名
    // private $acct_id; // 必须，收款人账号
    private $acct_type = '0'; // 账号类型，0 借记卡 1 贷记卡 2 对公账号
    // private $mobile; // 收款人手机
    // private $bank_name; // 开户行名称
    private $bank_settle_no; // 开户行支行联行号，对公账户必传，对私账户不传
    private $bank_branch_name; // 支行名称
    private $business_type = '20101'; // 必须，业务类型，默认20101业务往来款项
    private $business_no; // 业务号码
    private $memo; // 必须，备注附言
    // private $sign; // 必须，签名
    private $pubkey; // 公钥
    private $prikey; // 私钥
    private $md5key; // md5签名key

    private $plaintext;
    private $params = [];
    private $type;

    private static $_instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $parterKey    = json_decode($config['parterKey'], true);
        $this->spid   = $config['parterNo'];
        $this->pubkey = $parterKey['pubKey'];
        $this->prikey = $parterKey['priKey'];
        $this->md5key = $parterKey['saltKey'];
    }

    private function __clone()
    {
    }

    public static function getInstance($config = [])
    {
        if (is_null(self::$_instance) || isset(self::$_instance)) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }

    public function generateSignature($payInfo = [], $type = 'payment')
    {
        $this->type        = $type;
        $this->sp_serialno = $payInfo['orderNo'];

        if ($this->type == 'payment') {
            $this->params = [
                'tran_amt'         => $payInfo['tranAmt'],
                'cur_type'         => $this->cur_type,
                'pay_type'         => $this->pay_type,
                'acct_name'        => $payInfo['acctName'],
                'acct_id'          => $payInfo['acctId'],
                'acct_type'        => $this->acct_type,
                // 'mobile'           => $payInfo['mobile'],
                'bank_name'        => $payInfo['bankName'],
                'bank_settle_no'   => '',
                'bank_branch_name' => '',
                'business_type'    => '20101',
                'memo'             => '提现',
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'tfb_serialno' => $payInfo['tfbSerialNo'],
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'qry_type' => $payInfo['qryType'] ?? '1', // 1 只查余额、 2 所有账目(暂不支持)、 3 查询垫资可用额度、 4 查询垫资限额
            ];
        }

        $this->params['version']     = $this->version;
        $this->params['spid']        = $this->spid;
        $this->params['sp_serialno'] = $this->sp_serialno;
        $this->params['sp_reqtime']  = date('YmdHis');

        $signStr         = Utils::getSignatureString($this->params);
        $this->plaintext = $signStr . 'sign=' . strtolower(md5($signStr . 'key=' . $this->md5key));

        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        if ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('POST', $url, [
            'form_params' => [
                'cipher_data' => Utils::enCryptByRSA($this->plaintext, $this->pubkey),
            ],
        ]);
        if ($response->getStatusCode() == '200') {
            $returnXml = $response->getBody()->getContents();
            if ($returnXml) {
                $returnArr = Utils::toArray($returnXml);
                if ($returnArr['retcode'] == '00') {
                    $returnStr = Utils::deCryptByRSA($returnArr['cipher_data'], $this->prikey);
                    /**
                     * 下单：
                     * serialno_desc=交易成功&
                     * serialno_state=1&
                     * sp_serialno=20170905193147&
                     * spid=1800046681&
                     * tfb_rsptime=20170905193240&
                     * tfb_serialno=2017090500026878&
                     * sign=30d029635ad98d25d95654b8337672cd
                     *
                     * 查询：
                     * acct_id=6225****5784&
                     * acct_name=张三&
                     * bank_name=招商银行&
                     * bank_settle_no=308584000013&
                     * business_type=20101&
                     * cur_type=1&
                     * memo=代付测试&
                     * serialno_desc=交易成功&
                     * serialno_state=1&
                     * sp_serialno=20170905193147&
                     * spid=1800046681&
                     * tfb_rsptime=20170905193241&
                     * tfb_serialno=2017090500026878&
                     * tran_amt=1000&
                     * sign=756658662c38b6ad3bcd006f33c5e6f7
                     *
                     * 查询余额：
                     * account_status=1&
                     * available_balance=8853344338&
                     * sign=091cef2b6f95540ffe960c34321cca3c
                     */
                    parse_str($returnStr, $params);
                    if (Utils::verifySignature($params, $this->md5key)) {
                        return $params;
                    }
                    return null;
                }
                // 文档上说非00的情况，都按处理中处理，真实处理结果以反查接口返回的结果为准
                return ['serialno_state' => 2, 'tfb_serialno' => '', 'origData' => $returnArr];
            }
            return null;
        }
        return null;
    }

}
