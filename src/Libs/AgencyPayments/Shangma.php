<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Shangma
{
    private $gateway         = 'http://120.78.141.189/merchantPay/withdraw'; // 'http://pay.shangmafu.com/merchantPay/withdraw';
    private $queryUrl        = 'http://120.78.141.189/merchantPay/withdrawOrderQuery'; // 'http://pay.shangmafu.com/merchantPay/withdrawOrderQuery';
    private $balanceQueryUrl = 'http://120.78.141.189/merchantPay/accountQuery'; // 'http://pay.shangmafu.com/merchantPay/accountQuery';

    private $mchNo;
    private $outTradeNo;
    private $body     = '提现';
    private $bankType = 11;
    private $bankCode;
    private $remark;

    private $pubKey; // 公钥
    private $priKey; // 私钥
    private $merKey;
    private $md5Key; // md5签名key

    private $params = [];

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $parterKey    = json_decode($config['parterKey'], true);
        $this->mchNo  = $config['parterNo'];
        $this->pubKey = $parterKey['pubKey'];
        $this->priKey = $parterKey['priKey'];
        $this->md5Key = $parterKey['md5Key'];
        $this->merKey = $parterKey['merKey'];
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
        $this->type       = $type;
        $this->outTradeNo = $payInfo['orderNo'] ?? '';

        if ($this->type == 'payment') {
            $bankCode = $payInfo['bankCode'] ?? 'UNKNOWN';
            // 支行造假
            $randBranch = [];
            switch ($bankCode) {
                case 'BOC': // 中行
                    $randBranch = [
                        '江门开平支行',
                        '江门恩平西门路支行',
                        '江门恩平恩新东路支行',
                        '江门恩平新平北路支行',
                        '江门恩平东门路支行',
                        '江门恩平中澳豪庭支行',
                        '江门恩平圣堂支行',
                        '江门恩平江洲支行',
                    ];
                    break;
                case 'ABC': // 农行
                    $randBranch = [
                        '盘县红桥支行',
                        '凯里阳光支行',
                        '金沙安底支行',
                        '贵阳保税区支行',
                        '铜仁北门支行',
                        '沿河沿江支行',
                        '独山麻尾支行',
                        '福泉城厢支行',
                    ];
                    break;
                case 'ICBC': // 工行
                    $randBranch = [
                        '合水支行',
                        '金昌金川路支行',
                        '金昌金川西路支行',
                        '金昌牡丹支行',
                        '汕尾新区支行',
                        '丹江口跃进门支行',
                        '汕尾二马路东支行',
                        '丹江口跃进门支行',
                    ];
                    break;
                case 'CCB': // 建行
                    $randBranch = [
                        '高新北支行',
                        '后海公馆支行',
                        '海月支行',
                        '蛇口支行',
                        '沙河支行',
                        '南油支行',
                        '侨城东支行',
                        '南新支行',
                    ];
                    break;
                case 'BOCO': // 交行
                    $randBranch = [
                        '陕西分行',
                        '江苏分行',
                        '无锡分行',
                        '深圳分行',
                        '青岛分行',
                        '河南分行',
                    ];
                    break;
                default:
                    $randBranch = [
                        '东莞分行厚街支行',
                        '大连分行青泥支行',
                        '齐齐哈尔龙沙支行',
                        '威海青岛路支行',
                        '宜昌分行夷陵支行',
                    ];
            }
            $key            = array_rand($randBranch);
            $randBranchName = $randBranch[$key];
            $this->params   = [
                'mchNo'           => $this->mchNo,
                'outTradeNo'      => $this->outTradeNo,
                'amount'          => $payInfo['tranAmt'],
                'body'            => $this->body,
                'payDate'         => date('YmdHis'),
                'bankType'        => $this->bankType,
                'bankCode'        => $bankCode,
                'bankName'        => $payInfo['bankName'],
                'bankAccountNo'   => $payInfo['acctId'],
                'bankAccountName' => $payInfo['acctName'],
                'bankProvince'    => $payInfo['province'] ?? '省',
                'bankCity'        => $payInfo['city'] ?? '市',
                'bankBranch'      => (isset($payInfo['branch']) && empty($payInfo['branch'])) ? $randBranchName : $payInfo['branch'],
            ];
        } elseif ($this->type == 'query') {
            $this->params = [
                'mchNo'      => $this->mchNo,
                'outTradeNo' => $this->outTradeNo,
            ];
        } elseif ($this->type == 'balanceQuery') {
            $this->params = [
                'mchNo'     => $this->mchNo,
                'queryDate' => date('YmdHis'),
            ];
        }

        ksort($this->params);
        $md5Str = md5(urldecode(http_build_query($this->params)) . '&signKey=' . $this->md5Key);
        // $this->params['remark'] = $this->outTradeNo;
        $this->params['sign'] = Utils::rsaSign_string($md5Str, $this->priKey);

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
            $returnStr = $response->getBody()->getContents();
            $returnStr = mb_convert_encoding($returnStr, "UTF-8", "GBK");
            $returnStr = json_decode($returnStr, true);
            if (isset($returnStr['resultCode']) && $returnStr['resultCode'] == '00') {
                if ($this->verifySign($returnStr)) {
                    return $returnStr;
                } else {
                    return null;
                }
            } else {
                return $returnStr;
            }
            return null;
        }
        return null;
    }

    private function verifySign($params = [])
    {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $data = md5(urldecode(http_build_query($params)) . '&signKey=' . $this->md5Key);
        if (Utils::rsaVerify_string($data, $this->merKey, $sign)) {
            return true;
        }
        return false;
    }
}
