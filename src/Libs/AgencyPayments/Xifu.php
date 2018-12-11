<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Xifu
{
    private $gateway         = 'https://client.xifpay.com/agentPay/v1/batch/%s-%s'; // post
    private $queryUrl        = 'https://client.xifpay.com/agentPay/v1/batch/%s-%s'; // get
    private $balanceQueryUrl = 'https://client.xifpay.com/search/queryBalance'; // get

    private $batchBiztype = '00000';
    // 序号,银行账户,开户名,开户行名称,分行,支行,公/私,金额,币种,省,市,手机号,证件类型,证件号,用户协议号,商户订单号,备注
    private $batchContent = '1,%s,%s,%s,%s,%s,0,%s,CNY,%s,%s,%s,,,,%s,提现';
    private $batchCount   = 1;
    private $batchNo;
    private $batchVersion = '00';
    private $charset      = 'UTF-8';
    private $merchantId;
    private $signType = 'SHA';

    private $parterKey;

    private $params;

    private static $instance = null;

    private function __construct($config)
    {
        $this->client     = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->merchantId = $config['parterNo'];
        $this->parterKey  = $config['parterKey'];
    }

    private function __clone()
    {
    }

    public static function getInstance($config = [])
    {
        if (is_null(self::$instance) || isset(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function generateSignature($payInfo = [], $type = 'payment')
    {
        $this->type    = $type;
        $this->batchNo = $payInfo['orderNo'] ?? '';

        $this->params['signType'] = $this->signType;
        $this->params['sign']     = '';

        if ($this->type == 'payment') {
            $batchAmount                  = $payInfo['tranAmt'] / 100;
            $bankNo                       = $payInfo['acctId'];
            $username                     = $payInfo['acctName'];
            $bankName                     = $payInfo['bankName'];
            $branch                       = '分行'; // $payInfo['branch'];
            $subbranch                    = '支行'; // $payInfo['subbranch'];
            $province                     = '省'; // $payInfo['province'];
            $city                         = '市'; // $payInfo['city'];
            $mobile                       = ''; // $payInfo['mobile'];
            $this->params['batchAmount']  = $batchAmount; // 分转换为元，自动出款传过来是分、手动传过来也必须是分
            $this->params['batchBiztype'] = $this->batchBiztype;
            $this->params['batchContent'] = sprintf($this->batchContent, $bankNo, $username, $bankName, $branch, $subbranch, $batchAmount, $province, $city, $mobile, $this->batchNo);
            $this->params['batchCount']   = $this->batchCount;
        } elseif ($this->type == 'balanceQuery') {
            $this->params['customerNo'] = $this->merchantId;
        }

        if (in_array($this->type, ['payment', 'query'])) {
            $this->params['batchDate']    = substr($this->batchNo, 0, 8); // date('Ymd');
            $this->params['batchNo']      = $this->batchNo;
            $this->params['batchVersion'] = $this->batchVersion;
            $this->params['charset']      = $this->charset;
            $this->params['merchantId']   = $this->merchantId;
        }

        $this->params['sign'] = strtoupper(sha1(Utils::getXifuSignStr($this->params) . $this->parterKey));

        return $this;
    }

    public function sendRequest()
    {
        $method = 'POST';
        $option = [
            'form_params' => $this->params,
            'verify'      => false,
        ];
        $url = $this->gateway;
        if ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        if (in_array($this->type, ['query', 'balanceQuery'])) {
            $method = 'GET';
            unset($option['form_params']);
            $option['query'] = $this->params;
        }
        $response = $this->client->request($method, $url, $option);
        if ($response->getStatusCode() == '200') {
            // 下发：{"respMessage":"","respCode":"S0001"}
            // 查询：{"respCode":"S0001","respMessage":null,"charset":"UTF-8","merchantId":"100000000002565","batchVersion":"00","batchDate":"20180125","batchNo":"20180125114029459","batchContent":"1,20180125114029459,6228480616720139172,吴俊豪,分行,江门泰富支行,农业银行,0,1.00,CNY,提现,,失败,余额不足","sign":"28FE91574A84FE352C58E7B962A937C6968E6C68","signType":"SHA"}
            $returnStr = $response->getBody()->getContents();
            if (in_array($this->type, ['payment', 'query'])) {
                $returnStr = json_decode($returnStr, true);
            }
            if ($this->type == 'payment') {
                return $returnStr;
            } elseif ($this->type == 'query' && $this->verifySign($returnStr)) {
                // return json with sign 需要验签
                return $returnStr;
            } elseif ($this->type == 'balanceQuery') {
                // xml
                return Utils::toArray($returnStr);
            }
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $signPars = "";
        ksort($params);
        foreach ($params as $k => $v) {
            if ("" != $v && "sign" != $k && "signType" != $k && "respCode" != $k && "respMessage" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        return $sign == strtoupper(sha1(rtrim($signPars, '&') . $this->parterKey));
    }
}
