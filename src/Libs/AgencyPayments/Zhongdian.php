<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Zhongdian
{
    private $gateway         = 'http://39.108.141.206/pay/gateway.do';
    private $queryUrl        = 'http://39.108.141.206/pay/gateway.do';
    private $balanceQueryUrl = '';
    private $consumerNo;
    private $merOrderNo;
    private $sign;

    private $params = [];
    private $type;

    private static $_instance = null;

    private function __construct($config)
    {
        $this->client     = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->consumerNo = $config['parterNo'];
        $this->parterKey  = $config['parterKey'];
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
        $this->type       = $type;
        $this->merOrderNo = $payInfo['orderNo'];

        if ($this->type == 'payment') {
            $this->params = [
                'service'      => 'pay.to.other',
                'out_trade_no' => $this->merOrderNo,
                'bank_no'      => $payInfo['acctId'],
                'card_name'    => $payInfo['acctName'],
                // 'phone_no'     => $payInfo['mobile'],
                'total_fee'    => $payInfo['tranAmt'],
                'fee_type'     => 'CNY',
                'goods_desc'   => 'TX',
                'pp_flag'      => '01',
            ];
        }

        if ($this->type == 'query') {
            $this->params = [
                'service'        => 'unified.trade.query',
                'out_trade_no'   => $this->merOrderNo,
            ];
        }

        if ($this->type != 'balanceQuery') {
            $this->params['mch_id']    = $this->consumerNo;
            $this->params['nonce_str'] = md5(mt_rand(1000, 9999));
        }

        $this->sign = strtoupper(md5(Utils::getSignStr($this->params) . 'key=' . $this->parterKey));

        $this->params['sign'] = $this->sign;

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
            'body'    => Utils::toXml($this->params),
            'verify'  => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            ],
        ]);
        if ($response->getStatusCode() == '200') {
            $returnData = $response->getBody()->getContents();
            $returnData = Utils::toArray($returnData);
            if ($returnData && Utils::isZiyoufuSign($returnData, $this->parterKey)) {
                return $returnData;
            }
            return null;
        }
        return null;
    }
}
