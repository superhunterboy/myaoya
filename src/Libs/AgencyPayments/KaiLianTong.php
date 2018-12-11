<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;
use DOMDocument;

class KaiLianTong
{
    private $gateway         = 'https://pg.openepay.com/gateway/singleagentpay';
    private $queryUrl        = 'https://query.openepay.com/mchtoq/agentPaySingleQuery';
    private $balanceQueryUrl = 'https://query.openepay.com/mchtoq/accountBalance';

    // 下单
    private $mchtId;
    private $mchtOrderNo;
    private $remark;
    private $notifyUrl       = 'http://157a.com';

    private $pubkey; // 公钥
    private $prikey; // 私钥
    private $md5key; // md5签名key

    private $client;
    private $type;
    private $requestStr;

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $parterKey    = json_decode($config['parterKey'], true);
        $this->mchtId = $config['parterNo'];
        $this->pubkey = $parterKey['pubKey'];
        $this->prikey = $parterKey['priKey'];
        $this->md5key = $parterKey['saltKey'];
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
        $this->type          = $type;
        $this->mchtOrderNo    = $payInfo['orderNo'];

        $params = [];

        if ($this->type == 'payment') {
            $params = [
                'request' => [
                    'envelope' => [
                        'head' => [
                            'version' => 'v1.0.7.6',
                            'charset' => 'UTF-8',
                        ],
                        'body' => [
                            'mchtId'        => $this->mchtId,
                            'mchtOrderNo'   => $this->mchtOrderNo,
                            'accountNo'     => $payInfo['acctId'],
                            'accountName'   => $payInfo['acctName'],
                            'accountType'   => 'PERSONAL',
                            'bankNo'        => '000000000000',
                            'bankName'      => $payInfo['bankName'],
                            'amt'           => $payInfo['tranAmt'],
                            'remark'        => $this->remark,
                            'notifyUrl'     => $this->notifyUrl,
                            'orderDateTime' => date('YmdHis'),
                        ],
                    ],
                    'sign'     => [
                        'signType'    => '1',
                        'certificate' => '',
                        'signContent' => '',
                    ],
                ],
            ];
        } elseif ($this->type == 'query') {
            $params = [
                'request' => [
                    'envelope' => [
                        'head' => [
                            'version' => 'v1.0.7.7',
                            'charset' => 'UTF-8',
                        ],
                        'body' => [
                            'mchtId'              => $this->mchtId,
                            'mchtOrderNo'         => $this->mchtOrderNo,
                            'mchtBatchNo'         => '',
                            'paymentBusinessType' => 'SINGLE_PAY',
                            'orderDate'           => date('Ymd'),
                        ],
                    ],
                    'sign'     => [
                        'signType'    => '1',
                        'certificate' => '',
                        'signContent' => '',
                    ],
                ],
            ];
        } elseif ($this->type == 'balanceQuery') {
            $params = [
                'request' => [
                    'envelope' => [
                        'head' => [
                            'version' => 'v1.0.7.8',
                            'charset' => 'UTF-8',
                        ],
                        'body' => [
                            'mchtId'      => $this->mchtId,
                            'accountType' => 'AGENTPAY_PRESTORE_ACCOUNT',
                        ],
                    ],
                    'sign'     => [
                        'signType'    => '1',
                        'certificate' => '',
                        'signContent' => '',
                    ],
                ],
            ];
        }

        $signStr                                  = $this->arrayToXml($params['request']['envelope']);
        $signStr                                  = trim(substr($signStr, strpos($signStr, "<envelope>")));
        $params['request']['sign']['signContent'] = Utils::privateSign($signStr, __DIR__ . '/../../../certs/' . $this->prikey, $this->md5key);
        $requestStr                               = $this->arrayToXml($params['request']);
        $requestStr                               = trim(substr($requestStr, strpos($requestStr, "<request>")));

        $this->requestStr                         = base64_encode($requestStr);

        return $this;
    }

    public function sendRequest()
    {
        /**
        下单
        Array
        (
            [response] => Array
                (
                    [envelope] => Array
                        (
                            [head] => Array
                                (
                                    [version] => v1.0.7.6
                                    [charset] => UTF-8
                                )
                            [body] => Array
                                (
                                    [mchtId] => 100020091219001
                                    [mchtOrderNo] => E20171222101424
                                    [responseCode] => E0000
                                    [responseMsg] => 正常
                                    [status] => TX_BEGIN
                                )
                        )
                    [sign] => Array
                        (
                            [signType] => 1
                            [certificate] =>
                            [signContent] => MDOhSj/mUWIUZ1rwI4UK3eQir7t/NtgFC50bWMSuHRNsL9sFIXvGEQ9oeugOC+LSgJCumoW40EZQ2dFSAOueEOcd7CcLKKmSImQJMSaiMc/cn5MxtwomHj7YyjGz8jNJqTDZNa5A5FiLQMc+eFfRKjgX1ePwJcFWNieWKNoPqzvE9unjTibVkPWqbuuP1r5HCBGHGRPUr
        o3q0+sYlqJi38p1pe2ygVGlrLCxosrkQIwAln8fQ7gh3kCq0zicUUeDAU71U1BzHdS3e142LTis1Q0vBW/fSrCgO2MWl0nexs9QNE1+EGNLj3WdKUrlx8e7wWPPq/9R83zaZ96jLcDnNw==
                        )
                )
        )
        查询
        Array
        (
            [response] => Array
                (
                    [envelope] => Array
                        (
                            [head] => Array
                                (
                                    [version] => v1.0.7.7
                                    [charset] => UTF-8
                                )
                            [body] => Array
                                (
                                    [responseCode] => E0000
                                    [responseMsg] => 正常
                                    [status] => TX_SUCCESS
                                    [orderDesc] => 交易成功
                                    [mchtId] => 100020091219001
                                    [mchtOrderNo] => E20171222101424
                                    [amount] => 100
                                )
                        )
                    [sign] => Array
                        (
                            [signType] => 1
                            [certificate] =>
                            [signContent] => nDgN+NJoIMNMiDtSZpRgCOjccF3FQob1PJF73EWonVQmI5wCaJAm52Cy3ofe8rhi8GZFowpDB1qwhcJNwahwEWx+RYk+7r+xg5rAFB1/z45gIrACN9J099b6xdp5T3b3+aaaxcVFehMO7N5OJF00rKL2ICYJ5yciOQ3TlTSwZIlo+EszS56sNwFweELkwxDvAmEg4B+ep3eW0ICfVwNA+ze5iSTL6NTIjT/ZWfmp8uAwDdSnaPSWvVaqj9u94Pe1EqJ7lysykFGUB+kvTxXXXD2iYkkea9sKHIddhBGCUzcRotTczk/V3R9O49B32uXVXMMsRjidorJVB17DPmpFoA==
                        )
                )
        )
        余额查询
        Array
        (
            [response] => Array
                (
                    [envelope] => Array
                        (
                            [head] => Array
                                (
                                    [version] => v1.0.7.8
                                    [charset] => UTF-8
                                )
                            [body] => Array
                                (
                                    [responseCode] => E0000
                                    [responseMsg] => 查询成功
                                    [mchtId] => 100020091219001
                                    [accountBalance] => 997435558946
                                    [creditAmount] => 0
                                )
                        )
                    [sign] => Array
                        (
                            [signType] => 1
                            [certificate] =>
                            [signContent] => Uy/grPrvKgPc3GlIrb4/5loadFXZfnF9ERvOygbGANPz3rs0Ac66wacuZ73OotgS3v6Fm2ijbO546+v47jdSDFry0ZlP758aQkgpM7iRhp8lIdcDuPtTTY6VQpw39PBbllK9vgH5ipijxmsuPH3ZB5zNIMaG04z6yyMc6b91VsaQwjk8fhSsx03/XgmtEMDgYPteIW/YiRq0CqcdRmq2Xhq3DydLdkhz/0mpOdsnzNnS7i6ujBJ9qp7VDyl3kMBp7K2/qjFdsV/S7j4ggDtTFdxhPu0jmQzn+q43WzNoN1hISX6JDXzyE0HzadfF8OH+AYtNkSc9y1wSYmgYqpIKbg==
                        )
                )
        )
        */

        $url = $this->gateway;
        if ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('POST', $url, [
            'form_params' => [
                'reqMsg' => $this->requestStr,
            ],
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr   = $response->getBody()->getContents();
            $responseStr = base64_decode($returnStr);
            $signStr     = substr($responseStr, strpos($responseStr, "<envelope>"), strpos($responseStr, "</envelope>") + 1);
            $responseArr = Utils::xmlToArray($responseStr);
            if (isset($responseArr['response']['sign'])) {
                $sign        = trim($responseArr['response']['sign']['signContent']);
                $verify      = Utils::publicVerify($signStr, __DIR__ . '/../../../certs/' . $this->pubkey, $sign);
                if ($verify == 1) {
                    return $responseArr;
                }
                return null;
            }
            return $responseArr;
        }
        return null;
    }

    public function arrayToXml($arr, $dom = 0, $item = 0)
    {
        if (!$dom) {
            $dom = new DOMDocument("1.0", "UTF-8");
        }
        if (!$item) {
            $ccc = array_keys($arr);
            if ($ccc[0] == 'envelope') {
                $str_head = 'request';
            } else {
                $str_head = 'envelope';
            }
            $item = $dom->createElement($str_head);
            $dom->appendChild($item);
        }
        foreach ($arr as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "record");
            $item->appendChild($itemx);
            if (!is_array($val)) {
                $text = $dom->createTextNode($val);
                $itemx->appendChild($text);
            } else {
                $this->arrayToXml($val, $dom, $itemx);
            }
        }
        return $dom->saveXML();
    }
}
