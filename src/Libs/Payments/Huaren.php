<?php

namespace Weiming\Libs\Payments;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Weiming\Libs\Payments\WMPay;
use \Weiming\Libs\Utils;

class Huaren implements WMPay
{
    public $payType = [
        1 => [
            '1003' => '微信扫码',
        ],
        2 => [
            '1009' => '支付宝扫码',
        ],
        3 => [
            '308584000013' => '招商银行',
            '102100099996' => '中国工商银行',
            '105100000017' => '中国建设银行',
            '103100000026' => '中国农业银行',
            '104100000004' => '中国银行',
            '310290000013' => '浦发银行',
            '301290000007' => '交通银行',
            '306581000003' => '广东发展银行',
            '302100011000' => '中信银行',
            '303100000006' => '中国光大银行',
            '309391000011' => '兴业银行',
            '313584099990' => '平安银行',
            '304100040000' => '华夏银行',
            '403100000004' => '中国邮政储蓄',
        ],
        4 => [
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

    public $getwayUrl = 'http://api.hr-pay.com/PayInterface.aspx';

    // public $parterNo;

    public $parterKey;

    // public $callbackUrl;
    // public $notifyUrl;

    public $v_pagecode = '1001'; // 默认为网银
    public $v_mid; // 商户号
    public $v_oid; // 订单编号，格式为：订单生日期-商户编号-商户流水号
    public $v_rcvname;
    public $v_rcvaddr;
    public $v_rcvtel;
    public $v_goodsname;
    public $v_goodsdescription;
    public $v_rcvpost;
    public $v_qq;
    public $v_amount;
    public $v_ymd;
    public $v_orderstatus = 1;
    public $v_ordername;
    public $v_app;
    public $v_bankno;
    public $v_moneytype = 0;
    public $v_url;
    public $v_noticeurl;
    public $v_md5info;

    public $pubKey;
    public $priKey;
    public $saltKey;

    public $orderId;

    public $params     = [];
    public $lastParams = [];

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

            $this->v_mid       = $conf['parterNo'];
            $this->v_rcvname   = $conf['parterNo'];
            $this->v_rcvaddr   = $conf['parterNo'];
            // $this->v_rcvtel    = $conf['parterNo'];
            $this->v_rcvpost   = $conf['parterNo'];
            $this->v_ordername = $conf['parterNo'];
            $this->parterKey   = $conf['parterKey'];
            $this->v_url       = $conf['callbackUrl'];
            $this->v_noticeurl = $conf['notifyUrl'];

            $rsaKey        = json_decode($this->parterKey, true);
            $this->priKey  = $rsaKey['priKey'];
            $this->pubKey  = $rsaKey['pubKey'];
            $this->saltKey = $rsaKey['saltKey'];

        }

    }

    private function __clone()
    {}

    public function getPayType()
    {
        return $this->payType;
    }

    public function signature($type, $money, $orderId)
    {
        $currDate                 = date("Ymd");
        $this->v_oid              = $currDate . '-' . $this->v_mid . '-' . $orderId;
        $this->v_goodsname        = $orderId;
        $this->v_goodsdescription = $orderId;
        $this->v_ymd              = $currDate;
        $this->v_qq               = '361494897';
        $this->v_amount           = $money;
        $this->orderId            = $orderId;

        $this->params = [
            'v_pagecode'         => $this->v_pagecode,
            'v_mid'              => $this->v_mid,
            'v_oid'              => $this->v_oid,
            'v_rcvname'          => $this->v_rcvname,
            'v_rcvaddr'          => $this->v_rcvaddr,
            // 'v_rcvtel'           => $this->v_rcvtel,
            'v_goodsname'        => $this->v_goodsname,
            'v_goodsdescription' => $this->v_goodsdescription,
            'v_rcvpost'          => $this->v_rcvpost,
            'v_qq'               => $this->v_qq,
            'v_amount'           => $this->v_amount,
            'v_ymd'              => $this->v_ymd,
            'v_orderstatus'      => $this->v_orderstatus,
            'v_ordername'        => $this->v_ordername,
            'v_app'              => $this->v_app,
            'v_bankno'           => $this->v_bankno,
            'v_moneytype'        => $this->v_moneytype,
            'v_url'              => $this->v_url . '?oid=' . $orderId,
            'v_noticeurl'        => $this->v_noticeurl . '?oid=' . $orderId,
        ];

        if (!in_array($type, ['1003', '1009'])) {
            $this->v_pagecode           = '1001';
            $this->v_bankno             = $type;
            $this->params['v_pagecode'] = $this->v_pagecode;
            $this->params['v_bankno']   = $this->v_bankno;
            unset($this->params['v_app']);
        } else {
            $this->v_pagecode           = $type;
            $this->v_app                = '';
            $this->params['v_pagecode'] = $this->v_pagecode;
            $this->params['v_app']      = $this->v_app;
            unset($this->params['v_bankno']);
        }

        $signStr = '';
        foreach ($this->params as $k => $v) {
            $signStr .= $k . $v;
        }
        $this->v_md5info           = md5($signStr . $this->saltKey);
        $this->params['v_md5info'] = $this->v_md5info;

        $this->lastParams['data']  = Utils::enCrypt('[' . json_encode($this->params) . ']', $this->pubKey, isset($this->params['v_bankno']));
        $this->lastParams['mid']   = $this->v_mid;
        $this->lastParams['state'] = 0;

        return $this;
    }

    public function payment()
    {
        $getUrl = $this->getwayUrl . '?data=' . $this->lastParams['data'] . '&mid=' . $this->lastParams['mid'] . '&state=' . $this->lastParams['state'];
        // 网银支付直接跳转
        if ($this->params['v_pagecode'] == '1001') {
            return $getUrl;
        } else {
            $onRedirect = function (RequestInterface $request, ResponseInterface $response, UriInterface $uri) {
                // echo 'Redirecting ' . $request->getUri() . ' to ' . $uri . "\n";
            };
            $client = new Client();
            $res    = $client->request('GET', $this->getwayUrl, [
                'debug'           => false,
                'stream'          => true,
                'query'           => $this->lastParams,
                'allow_redirects' => [
                    'max'             => 10,
                    'strict'          => true,
                    'referer'         => true,
                    'protocols'       => ['http', 'https'],
                    'on_redirect'     => $onRedirect,
                    'track_redirects' => true,
                ],
            ]);
            if ($res->getStatusCode() == '200') {
                $body    = '';
                $resData = $res->getBody();
                while (!$resData->eof()) {
                    $body .= $resData->read(1024);
                }
                // var_dump($res->getHeaderLine('X-Guzzle-Redirect-Status-History'));
                $redirectUrl = $res->getHeaderLine('X-Guzzle-Redirect-History');
                if ($body && !preg_match("/<.*<\/html>/", Utils::formatHtml($body))) {
                    $body = Utils::deCrypt($body, $this->priKey);
                    $tmpArr = json_decode(rtrim(ltrim(urldecode($body), '['), ']'), true);
                    $tmpArr['order'] = $this->orderId;
                    return json_encode($tmpArr);
                } else {
                    return $redirectUrl;
                }
            }
        }
    }

    public function verifySign($params = [])
    {
        $data = Utils::deCrypt($params['data'], $this->priKey);
        $data = json_decode(urldecode($data), true);
        $ret  = $data[0];
        if (Utils::verifySign($ret, $this->saltKey) === true) {
            return $ret;
        }
        return false;
    }

    public function notifyDeCrypt($data)
    {
        $data = Utils::deCrypt($data, $this->priKey);
        $data = json_decode(rtrim(ltrim(urldecode($data), '['), ']'), true);
        if (Utils::verifySign($data, $this->saltKey) === true) {
            return $data;
        }
        return false;
    }

    public function query($orderId)
    {
    }
}
