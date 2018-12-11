<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Bingo
{
    private $gateway         = 'https://www.bingopay.net/open-gateway/payment/invoke';
    private $queryUrl        = 'https://www.bingopay.net/open-gateway/payment/invoke';
    private $balanceQueryUrl = 'https://www.bingopay.net/open-gateway/payment/invoke';

    private $bankInfo = [
        '102' => '工商银行',
        '103' => '农业银行',
        '104' => '中国银行',
        '105' => '建设银行',
        '203' => '农业发展银行',
        '301' => '交通银行',
        '302' => '中信银行',
        '303' => '光大银行',
        '304' => '华夏银行',
        '305' => '民生银行',
        '306' => '广发银行',
        '307' => '平安银行',
        '308' => '招商银行',
        '309' => '兴业银行',
        '310' => '浦发银行',
        '313' => '北京银行',
        '315' => '恒丰银行',
        '316' => '浙商银行',
        '318' => '渤海银行',
        '325' => '上海银行',
        '403' => '邮储银行',
        '440' => '徽商银行',
    ];

    private $md5Key;
    private $desKey;
    private $orgId;
    private $merno;
    private $orderNo;

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->orgId = $config['parterNo'];
        $keyArr      = json_decode($config['parterKey'], true); // {"merno":"...","md5Key":"...","desKey":"..."}
        if (is_array($keyArr)) {
            $this->merno  = $keyArr['merno'];
            $this->md5Key = $keyArr['md5Key'];
            $this->desKey = $keyArr['desKey'];
        }
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

        $businessData = [];

        if ($this->type == 'payment') {
            $bankCode = $payInfo['bankCode'];
            $businessData = [
                'merno'        => $this->merno,
                'method'       => 'payment.doPayment',
                'accountcode'  => $payInfo['accountCode'], // 余额查询接口返回字段
                'accounttype'  => $payInfo['accountType'] ?? 0, // 0-从余额代付 1-从T1余额代付，余额查询接口返回字段来确定
                'amount'       => $payInfo['tranAmt'], // 分
                'order_id'     => $this->orderNo,
                'bank_unionid' => '', // 联行号，http://www.lianhanghao.com
                'cardname'     => $this->bankInfo[$bankCode], // $payInfo['bankName'], // 银行名称
                'bank_code'    => $bankCode, // 银行编码
                'cardno'       => $payInfo['acctId'],
                'name'         => $payInfo['acctName'],
                'smsseq'       => '', // 验证码单号
                'smscode'      => '', // 验证码
            ];
            // print_r($businessData);exit();
        } elseif ($this->type == 'query') {
            $businessData = [
                'merno'    => $this->merno,
                'method'   => 'payment.queryPayment',
                'amount'   => '',
                'order_id' => $this->orderNo,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $businessData = [
                'merno'  => $this->merno,
                'method' => 'payment.loadBanlance',
            ];
        }

        $this->params = [
            'requestId'    => $this->orderNo,
            'orgId'        => $this->orgId,
            'timestamp'    => date('YmdHis'),
            'productId'    => '9500',
            'businessData' => urlencode(Utils::des_ecb_encrypt(json_encode($businessData), $this->desKey)), // DES加密
            'dataSignType' => 1,
        ];

        $temp = $this->getSignStr($this->params);

        $this->params['signData'] = strtoupper(md5($temp . $this->md5Key));

        return $this;
    }

    public function sendRequest()
    {
        // print_r($this->params);
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
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);

            // 下单
            // Array
            // (
            //     [key] => 05
            //     [msg] => 代付申请成功
            //     [requestId] => 201806261416537264430
            //     [respCode] => 00
            //     [respMsg] => 通讯成功
            //     [result] => JSON
            //     {
            //             "accountcode":"958",
            //             "accounttype":"0",
            //             "amount":"1",
            //             "bank_code":"308",
            //             "bank_unionid":"",
            //             "cardname":"招商银行",
            //             "cardno":"6226096524880818",
            //             "merno":"312018042223067147",
            //             "method":"payment.doPayment",
            //             "name":"张三",
            //             "order_id":"201806261416537264430",
            //             "skipUpmer":[],
            //             "smscode":"",
            //             "smsseq":""
            //     }
            //     [status] => 3
            // )

            // 查询
            // Array
            // (
            //     [key] => 00
            //     [msg] => 查询成功
            //     [requestId] => 201806261416537264430
            //     [respCode] => 00
            //     [respMsg] => 通讯成功
            //     [result] => JSON
            //     {
            //            "amount":"1",
            //            "merno":"312018042223067147",
            //            "method":"payment.queryPayment",
            //            "order_id":"201806261416537264430",
            //            "plat_order_sn":"20180626141655423LfDi",
            //            "skipUpmer":[],
            //            "status":1
            //     }
            //     [status] => 1
            // )

            // 余额查询
            // Array
            // (
            //     [key] => 00
            //     [msg] => 账户查询成功
            //     [requestId] => 201806261433086475780
            //     [respCode] => 00
            //     [respMsg] => 通讯成功
            //     [result] => {"accountinfo":[{"d0frozen":201,"t1can_use":0,"d0balance":4980,"t1balance":0,"accountcode":958,"d0can_use":4779,"account_name":"SYX（雨157）4081","t1frozen":0}],"merno":"312018042223067147","method":"payment.loadBanlance","skipUpmer":[]}
            //     [status] => 1
            // )

            if (isset($returnStr['result']) && $returnStr['result'] && Utils::isJSON($returnStr['result'])) {
                $result = json_decode($returnStr['result'], true);
                if (isset($returnStr['result'])) {
                    unset($returnStr['result']);
                }
                if (isset($returnStr['status'])) {
                    unset($returnStr['status']);
                }
                $returnStr = array_merge($returnStr, $result);
            }
            return $returnStr;
        }
        return null;
    }

    private function verifySign($params = [])
    {
    }

    private function getSignStr($params = [])
    {
        ksort($params);
        $temp = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $temp .= $key . '=' . $value . '&';
            }
        }
        return substr($temp, 0, strlen($temp) - 1);
    }
}
