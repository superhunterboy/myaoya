<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class SDpay
{
    private $callback;
    private $pubKey;
    private $merchantNo;
    private $content;
    private $orderNo;
    private $priKey = 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAN65y5jwY0yol1x4PDPQ2EHhJDfYZkuvXmdKPFHUrQOPv1ljKMqhyyvsOO0srt2s5YGF6Oi/HADvwKmtvZjb1QyjM76DOZ7zZzKqiBVH6xxq5T9cV5G3pKv22YFBAjwpIWTwhx5uakvCvxASVz53AhF2OiLyXFA7sPhZ061W6bxJAgMBAAECgYAPPEBcFt2ECehIsATWOowAfakflNLyjG11vVNA+b5YBwY81BafPdALEh6Wwr63dTNB1+oluaTtv3i7YqIcibUaBob3JlvVvLqeimoXpQ4GjDiWG1rbxxdUdeL/p6M5TDoBt52vX9YI9ojzccHVV3L4SH17XQPy4BYteZClN+kGTQJBAPP1kehKwHqmZKabPBWibh2+1deDPGzEfmfy+93U7LqRsA2r/nlx/E9FszyLexM0aN8iBv/cZwmDcWjsk6dsG0sCQQDpt/CR2rSjj63rrTsCGz+PufOohZQNp0Uv6MY4z/KrEyoku1Fv1fHhk6HG7tHg2Dz6lXZQwvU+OVDSDzM8lRY7AkAjTr5APVlgJubYSg1HIEwJmA4A8+hx4Xpd9wfG0kM0uVMel27sCrg2jOLk91bnr6Uh7h3POAndkZ1/TwYLrBTDAkEAh0CBIYb1Xmn5dhervE5UfDJaRT34LeaM7WrBu4LrpgDTNKqKODQRZD9xMZRnNL0unLz92ULZdYbptIZDWl3UnQJAFPH4tmq8fRl6Cg7QPbdt//tlOQZadsfMkZVRLPCcbBj7sVrQnFkJU47m7nyPIMsCvUTuFDHgcFP8WzeERKEkqA==';

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->merchantNo = $config['parterNo'];
        $this->callback   = $config['callbackUrl'];
        $this->pubKey     = $config['parterKey'];
    }
    private $gateway         = 'https://apimt.pr0pay.com/withdraw/merchant/'; // 生产环境下单地址
    private $queryUrl        = 'https://apimt.pr0pay.com/withdraw/merchant/'; // 查询接口地址
    private $balanceQueryUrl = 'https://apimt.pr0pay.com/merchant/'; // 查询余额地址

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
        $this->type = $type;
        $url        = "http://47.244.18.226:8094/publicKeyEncrypt";
        if ($this->type == 'payment') {
            $this->orderNo = $payInfo['orderNo'];
            $str           = $this->merchantNo . $payInfo['acctId'] . sprintf("%.2f", $payInfo['tranAmt']);
            $signatureKey  = Utils::md5WithRSA($str, $this->priKey);
            $this->params  = [
                'merchantCode'   => $this->merchantNo, // 商戶號
                'intoAmount'     => sprintf("%.2f", $payInfo['tranAmt']), // 转入金额,数值必须大于0
                'type'           => 'online',
                'intoAccount'    => $payInfo['acctId'], // 转入卡号
                'intoName'       => $payInfo['acctName'], // 转入姓名
                'intoBank'       => $payInfo['bankName'], // 转入银行
                'intoBankBranch' => '中国农业银行焦作山阳商城支行', // 转入支行
                'intoBankCode'   => $payInfo['bankCode'] ?? '', // 转入銀行編碼
                'intoProvince'   => '41', // 转入银行所在省
                'intoCity'       => '4103', // 转入银行所在市
                'asyncURL'       => $this->callback, //異部通知回調位置
                'serialNumber'   => $payInfo['orderNo'], // 商家流水號
                'requestTime'    => date('YmdHis'), // 請求時間
                'signature'      => $signatureKey,
            ];

            $str           = stripslashes(json_encode($this->params, JSON_UNESCAPED_UNICODE));
            $res           = $this->send_curl('8094', $url, $str);
            $con_str       = $res['message'];
            $this->content = [
                'content' => $con_str,
            ];
        } elseif ($this->type == 'query') {
            $orderNo      = $payInfo['orderNo'];
            $str          = $this->merchantNo . $orderNo;
            $signatureKey = Utils::md5WithRSA($str, $this->priKey);
            $this->params = [
                'merchantCode' => $this->merchantNo,
                'serialNumber' => $payInfo['orderNo'],
                'signature'    => $signatureKey,
            ];
            $str           = stripslashes(json_encode($this->params, JSON_UNESCAPED_UNICODE));
            $res           = $this->send_curl('8094', $url, $str);
            $con_str       = $res['message'];
            $this->content = [
                'content' => $con_str,
            ];

        } elseif ($this->type == 'balanceQuery') {
            $signatureKey = Utils::md5WithRSA($this->merchantNo, $this->priKey);
            $signatureKey = str_replace("=", "\u003d", $signatureKey);
            $this->params = [
                'merchantCode' => $this->merchantNo,
                'signature'    => $signatureKey,
            ];

            $str           = stripslashes(json_encode($this->params));
            $res           = $this->send_curl('8094', $url, $str);
            $con_str       = $res['message'];
            $this->content = [
                'content' => $con_str,
            ];
        }
        return $this;
    }

    public function sendRequest()
    {
        $url = '';

        $formatRsaKey = Utils::formatRsaKey($this->priKey, 'priKey');

        if ($this->type == 'query') {
            $url = $this->queryUrl . $this->merchantNo . '/transaction/status';
        } elseif ($this->type == 'balanceQuery') {
            // echo "111";die;
            $url = $this->balanceQueryUrl . $this->merchantNo . '/balance';
        } elseif ($this->type == 'payment') {
            $url = $this->gateway . $this->merchantNo . '/transaction';
        }
        $response = $this->client->request('POST', $url, [
            'form_params' => $this->content,
            'verify'      => false,
        ]);
        // echo $url.'---------';
        // print_r($this->content);die;
        // print_r($response);die;
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            $returnStr = json_decode($returnStr, true);
            return $returnStr;
            if (is_array($returnStr)) {
                $returnMsg  = $returnStr['results'];
                $returnRes  = json_decode($returnMsg, true)['securityCode'];
                $returnData = Utils::privateDecrypt(base64_decode($returnRes), $formatRsaKey);
                return $returnData;
                // if ($this->type == 'payment') {
                //     return $returnStr;
                // } else {
                //     $returnMsg = $returnStr['results'];
                //     $returnRes = json_decode($returnMsg, true)['securityCode'];
                //     return Utils::privateDecrypt(base64_decode($returnRes), $formatRsaKey);
                // }

            }
            return null;
        }

        return null;
    }

    public function send_curl($port, $url, $data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT           => "8094",
            CURLOPT_URL            => "http://47.244.18.226:8094/publicKeyEncrypt",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => array(
                "Accept: */*",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Type: application/json;charset='UTF-8'",
                "Host: 47.244.18.226:8094",
                "Postman-Token: 4b0ad932-2176-47df-a8f7-79d4a3338282,96340635-a1db-4052-b915-64ec9412d0fe",
                "User-Agent: PostmanRuntime/7.13.0",
                "accept-encoding: gzip, deflate",
                "api_key: eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJ5ZjAyMSIsImF1ZGllbmNlIjoiIiwiY3JlYXRlZCI6MTU1MzA3Njk3MjAzOSwiaXAiOiIxMTkuOS43Ni4xMCIsImlkIjoiMTQ1NDU0MzAiLCJleHAiOjE4Njg0MzY5NzJ9.Mi9Xgs4Pf6bVPDJrtNscYTAKmSjbhs2e7UFWGOSIe4Zxz3d6CqLs-oH3Wc8ZBKWL57F9rW12U0ep53mWh6br-A",
                "cache-control: no-cache",
                "content-length: 653",
            ),
        ));

        $response = curl_exec($curl);
        $err      = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return json_decode($response, true);
        }
    }

    public function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $jsonStr = json_encode($params, JSON_UNESCAPED_UNICODE);
        return $sign == md5($jsonStr . $this->md5Key);
    }

    private function getSignStr($params = [], $payKey = '', $md5key = '')
    {
        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key . '=' . $val . '&';
        }
        if ($payKey) {
            $sign .= 'pay_pwd=' . $payKey . '&key=' . $md5key;
        } else {
            $sign .= 'key=' . $md5key;
        }
        return md5($sign);
    }
}
