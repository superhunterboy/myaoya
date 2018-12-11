<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Shunxin
{
    private $gateway         = 'http://trade.fjjxjj.com/cgi-bin/netpayment/pay_gate.cgi';
    private $queryUrl        = 'http://trade.fjjxjj.com/cgi-bin/netpayment/pay_gate.cgi';
    private $balanceQueryUrl = 'http://trade.fjjxjj.com/cgi-bin/netpayment/pay_gate.cgi';

    private $parterKey; // md5签名key
    private $merchNo;
    private $orderNo;

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client    = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->merchNo   = $config['parterNo'];
        $this->parterKey = $config['parterKey'];
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
        $this->orderNo = $payInfo['orderNo'] ?? '';

        if ($this->type == 'payment') {
            $this->params = [
                'apiName'      => 'SINGLE_ENTRUST_SETT',
                'apiVersion'   => '1.0.0.0',
                'platformID'   => $this->merchNo,
                'merchNo'      => $this->merchNo,
                'orderNo'      => $this->orderNo,
                'tradeDate'    => date('Ymd'),
                'merchUrl'     => 'http://157a.com',
                'merchParam'   => '',
                'bankAccNo'    => $payInfo['acctId'],
                'bankAccName'  => $payInfo['acctName'],
                'bankCode'     => $payInfo['bankCode'],
                'bankName'     => $payInfo['bankName'] ?? $payInfo['branch'],
                'Amt'          => sprintf("%.2f", $payInfo['tranAmt'] / 100), // 分转元
                'tradeSummary' => '提现',
                'signMsg'      => '',
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'apiName'    => 'SINGLE_SETT_QUERY',
                'apiVersion' => '1.0.0.0',
                'platformID' => $this->merchNo,
                'merchNo'    => $this->merchNo,
                'orderNo'    => $this->orderNo,
                'tradeDate'  => date('Ymd'),
                'signMsg'    => '',
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'apiName'    => 'MERCH_ACC_BALA_QUERY',
                'apiVersion' => '1.0.0.0',
                'platformID' => $this->merchNo,
                'merchNo'    => $this->merchNo,
                'accDate'    => date('Ymd'),
                'signMsg'    => '',
            ];
        }

        $this->params['signMsg'] = $this->getSignStr($this->params);

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
            'form_params' => $this->params,
            'verify'      => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr         = $response->getBody()->getContents();
            $tmpArr            = Utils::xmlToArray($returnStr);
            $resArr            = $tmpArr['moboAccount']['respData'] ?? [];
            $resArr['signMsg'] = $tmpArr['moboAccount']['signMsg'] ?? '';
            $resArr['signXml'] = $returnStr;
            if ($this->verifySign($resArr)) {
                unset($resArr['signXml']);
                return $resArr;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $signXml = $this->filterResponseXml($params['signXml']);
        $signMsg = $params['signMsg'];
        $signStr = $signXml . $this->parterKey;
        return $signMsg == strtoupper(md5($signStr));
    }

    private function getSignStr($params = [])
    {
        unset($params['signMsg']);
        $uri = urldecode(http_build_query($params));
        $uri = $uri . $this->parterKey;
        return strtoupper(md5($uri));
    }

    private function filterResponseXml($xml = '')
    {
        // 剔除xml头
        $xml = substr($xml, strpos($xml, '<moboAccount>'));
        // 剔除标签 <moboAccount> 和 </moboAccount>
        $xml = preg_replace('#\<(\/)?moboAccount.*?\>#i', '', $xml);
        // 剔除 <signMsg>65B6E0CF8C9C4A9112531CEF5F99932D</signMsg>
        $xml = preg_replace('#\<signMsg.*?\>.*\<(\/)?signMsg.*?\>#i', '', $xml);

        return $xml;
    }
}
