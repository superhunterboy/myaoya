<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Xianfeng
{
    private $gateway         = 'https://p.ronghuanpay.com/AgtD0TranPay'; // 生产环境下单地址
    private $queryUrl        = 'https://p.ronghuanpay.com/709140.tran8'; // 查询接口地址
    private $balanceQueryUrl = 'https://p.ronghuanpay.com/709145.tran8'; // 查询余额地址

    private $md5Key; // md5签名key
    private $merchantNo;
    private $orderNo;
    private $callbackUrl;
    private $agtorg;
    private $pubKey = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDEyJBGdpE/QVhOKOAKBs11Fyq4
Gx8ED2DanFbJd2TZBJII3SqIqYwVM4UU6nsXrxbZ7ufYfsVtRecjs3Y2Uu/IZjfv
pVBneuTQ1y3LrSfzgNn4iv8flUihK0QcYzMAj6GMpbXM7WZV7l6oq+s4NisTPB8V
XxINQITKoPQdXUXbzQIDAQAB
-----END PUBLIC KEY-----';

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $parterKey         = json_decode($config['parterKey'], true);
        $this->merchantNo  = $config['parterNo'];
        $this->md5Key      = $parterKey['md5Key'];
        $this->agtorg      = $parterKey['agtorg'];
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
        $payKey     = '';
        $this->type = $type;

        if ($this->type == 'payment') {
            $this->orderNo = $payInfo['orderNo'];

            // $pubKey     = Utils::rsaSign_string($this->pubKey);

            $pubKey     = openssl_get_publickey($this->pubKey); //公钥 rf给出
            $strsss     = openssl_public_encrypt($payInfo['acctId'], $encrypted, $pubKey);
            $userCardNo = base64_encode($encrypted);

            $dataArr = [
                'ordernumber'   => 'D0' . $this->agtorg . $payInfo['orderNo'],
                'accountname'   => $payInfo['acctName'],
                'bankaccount'   => $userCardNo,
                'accounttype'   => '1',
                'bank'          => $payInfo['bankName'],
                'bankcode'      => $payInfo['bankCode'] ?? '',
                'mobilephone'   => '13244438888',
                'certificateno' => '43070119940604005X',
                'amount'        => $payInfo['tranAmt'] * 100,
                'remark'        => 'test',
                'notifyUrl'     => $payInfo['callbackUrl'],
                'ordertime'     => date('Y-m-d H:i:s', time()),
            ];
            ksort($dataArr);

            $data = Utils::encryptAES_CBC(json_encode($dataArr), $this->md5Key);

            $this->params = [
                'agtorg'    => $this->agtorg,
                'mercid'    => $this->merchantNo,
                'data'      => str_replace(array('+', '/', '='), array('-', '_', '.'), $data),
                'timestamp' => date('Y-m-d H:i:s', time()),
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'ordernumber' => 'D0' . $this->agtorg . $payInfo['orderNo'],
                'agtorg'      => $this->agtorg,
                'mercid'      => $this->merchantNo,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'agtorg' => $this->agtorg,
                'mercid' => $this->merchantNo,
            ];
        }
        ksort($this->params);
        $this->params['sign'] = $this->getSignStr($this->params, $this->md5Key);
        return $this;
    }

    public function sendRequest()
    {
        if ($this->type == 'payment') {

            $url      = $this->gateway;
            $response = $this->client->request('POST', $url, [
                'body'    => json_encode($this->params),
                'verify'  => false,
                'headers' => [
                    'Content-type' => 'application/json',
                ],
            ]);
            return json_decode($response->getBody()->getContents(),true);

        } elseif ($this->type == 'query') {
            $getdata  = http_build_query($this->params);
            $url      = $this->queryUrl . "?" . $getdata;
            $response = file_get_contents($url);
            $response = json_decode(json_encode(simplexml_load_string($response)), 1);
            return $response;
            $result   = '2';

            if ($response != '') {
                if ($response['RSPCOD'] == '000000' && $response['STATUS'] == '4') {
                    $result = '1';
                    $shipNo = $response['ORDERNUMBER'];
                }
                $ret_msg = $response['RSPCOD'] . '_' . $response['RSPMSG'];

                // return array('number' => $data['number'], 'shopNo' => $shopNo, 'result' => $result, 'money' => $money, 'ret_msg' => $ret_msg);
            }

        } elseif ($this->type == 'balanceQuery') {
            $getdata  = http_build_query($this->params);
            $url      = $this->balanceQueryUrl . "?" . $getdata;
            $response = file_get_contents($url);
            $response = json_decode(json_encode(simplexml_load_string($response)), 1);
            $state    = '2';

            if ($response != '') {
                if ($response['RSPCOD'] == '000000') {
                    $state        = '1';
                    $order_amount = $response['BANL'] / 100;
                }

                return array('money' => $order_amount, 'state' => $state);
            }
        }
    }

    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $jsonStr = json_encode($params, JSON_UNESCAPED_UNICODE);
        return $sign == md5($jsonStr . $this->md5Key);
    }

    private function getSignStr($params = [], $md5Key)
    {
        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key . '=' . $val . '&';
        }
        if ($sign) {
            $sign .= 'key=' . $md5Key;
            return strtoupper(md5($sign));
        }
    }
}
