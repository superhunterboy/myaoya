<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

//19
class Jiayoutong
{
    private $gateway         = 'http://open.jypay.com/gateway/gateway.htm';//交易类生产地址
    //private $gateway         = 'http://202.104.122.163:8006/gateway/gateway.htm';//交易类测试地址
    private $queryUrl        = '';
    private $balanceQueryUrl = '';

    private $parterKey; //秘钥
    private $version  = '2.0'; //版本号
    private $agency_no; //商户号

    private $pubkey; // 公钥
    private $prikey; // 私钥

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->agency_no = $config['parterNo']; //商户号
        $parterKey    = json_decode($config['parterKey'], true);
        $this->pubkey = $parterKey['pubKey'];
        $this->prikey = $parterKey['priKey'];
    }

    private function __clone()
    {
    }

    public static function getInstance($config = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function generateSignature($payInfo = [], $type = 'payment')
    {
        $this->type = $type;
        $userName   = $payInfo['acctName'] ?? '';
        $cardNo     = $payInfo['acctId'] ?? '';
        $bankName   = $payInfo['bankName'] ?? '';
        $orderNo    = $payInfo['orderNo'] ?? '';
        $mobile     = $payInfo['mobile'] ?? '';
        $amount     = number_format($payInfo['tranAmt'], 2, '.', '');
        $biz_content= [];
        $method     = 'jypay.webPay.colpay.pay';

        if ($this->type == 'payment') {
            $method = 'jypay.webPay.colpay.pay';
            $biz_content = [
                'merNo'       => $this->agency_no,
                'orderNo'     => $orderNo,
                'goodsName'   =>'Virtual',
                'bankName'    => $bankName,
                'amount'      => $amount,
                'cardNo'      => $cardNo,
                'cardHolder'  => $userName,
                //'cardNo'      => Utils::enCryptByRSA($cardNo, $this->pubkey),
                //'cardHolder'  => Utils::enCryptByRSA($userName, $this->pubkey),
                'cardType'    => 1,
                'payType'     => 1,
                'phoneNo'     => $mobile,
                //'phoneNo'     => Utils::enCryptByRSA($mobile, $this->pubkey),
                'channelCode' => 'CH',
                'notifyUrl'   => ''
            ];
        } elseif ($this->type == 'query') {
            $method = 'jypay.webPay.colpay.queryStatus';
            $biz_content = [
                'merNo'       => $this->agency_no,
                'orderNo'     => $orderNo,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $method = 'jypay.webPay.colpay.queryAccAmount';
            $biz_content = [
                'merNo'       => $this->agency_no,
            ];
        }

        $this->params = [
            'agency_no'   => $this->agency_no, //商户号
            'method'      => $method, //接口名称
            'timestamp'   => date('Y-m-d H:i:s'), //交易时间
            'version'     => $this->version, //交易时间
            'biz_content' => json_encode($biz_content, 320),
        ];
        //签名
        ksort($this->params);
        $this->params['sign'] = Utils::rsaSign_string(urldecode(http_build_query($this->params)), $this->prikey);
        //print_r($this->params);exit;
        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        // if ($this->type == 'query') {
        //     $url = $this->queryUrl;
        // } elseif ($this->type == 'balanceQuery') {
        //     $url = $this->balanceQueryUrl;
        // }
        // echo $url ,' <==> ', json_encode($this->params, JSON_UNESCAPED_UNICODE);
        // exit;
        $response = $this->client->request('POST', $url, [
            'form_params' => $this->params,
            'verify' => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);
            // 下单
            // {
            //  "code":"00",
            //  "msg":"success",
            //  "merNo":"商户号",
            //  "businessNo":"流水号",
            //  "status":"1, 状态",
            //  "describe":"状态描述",
            // }
            // 查询
            // {
            //  "code":"00",
            //  "msg":"success",
            //  "merNo":"商户号",
            //  "businessNo":"流水号",
            //  "status":"1, 状态",
            //  "describe":"状态描述",
            //  "tradeTime":"交易时间",
            //  "fee":"交易费用",
            // }
            // 余额查询
            // {
            //  "code":"00",
            //  "msg":"success",
            //  "merNo":"商户号",
            //  "amount":"账户金额",
            // }
            if (is_array($returnStr)) {
                return $returnStr;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
    }

    private function getSignStr()
    {

    }
}
