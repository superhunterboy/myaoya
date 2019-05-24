<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Huitian
{
    private $gateway         = 'https://gateway.huitianpay.com/Payment/BatchTransfer.aspx';
    private $queryUrl        = 'https://gateway.huitianpay.com/Payment/BatchQuery.aspx';
    private $balanceQueryUrl = 'https://gateway.huitianpay.com/Payment/QueryBalance.aspx';
    private $consumerNo;
    private $merOrderNo;
    private $sign;

    private $params = [];
    private $type;

    private static $_instance = null;

    private function __construct($config)
    {
        $this->client     = new Client();
        $this->merchantId = $config['parterNo'];
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
            $bankCode=$this->bankcodeht($payInfo['bankName']);
            $this->params = [
                'version'      => '2',
                'agent_id'     => $this->merchantId,
                'batch_no'     => $this->merOrderNo,
                'batch_amt'    => $payInfo['tranAmt'],
                'batch_num'    => 1,
                'detail_data'     => time().'^'.$bankCode.'^0^'.$payInfo['acctId'].'^'.$payInfo['acctName'].'^'.$payInfo['tranAmt'].'^daifu^北京市^北京市^中国农业银行股份有限公司北京海淀大街支行',
                'notify_url'   => 'http://www.baidu.com',
                'ext_param1'      => time(),
                'sign' => $this->parterKey
            ];

        }

        if ($this->type == 'query') {
            $this->params = [
                'version'        => '2',
                'agent_id'   => $this->merchantId,
                'batch_no'   => $this->merOrderNo,
                'sign'       => $this->parterKey,
            ];
        }

        if ($this->type == 'balanceQuery') {
          $this->params = [
              'version'        => '2',
              'agent_id'   => $this->merchantId,
              'sign'       => $this->parterKey,
          ];
        }

        return $this;
    }

    public function sendRequest()
    {
        $url = $this->gateway;
        if ($this->type == 'payment') {
            $url = $this->gateway;
              $signStr='';
            $signStr  = $signStr . 'agent_id='    . $this->params['agent_id'];
            $signStr  = $signStr . '&batch_amt='  . $this->params['batch_amt'];
            $signStr  = $signStr . '&batch_no='   . $this->params['batch_no'];
            $signStr  = $signStr . '&batch_num='  . $this->params['batch_num'];

            //获取提交url
            $detail_url = $signStr;
            $detail_url .='&detail_data='. strtolower(urlencode(iconv("utf-8","gb2312//IGNORE",$this->params['detail_data']))) ;
            $detail_url .='&notify_url=' . $this->params['notify_url'];
            $detail_url .='&ext_param1=' . $this->params['ext_param1'];
            $detail_url .="&version=2";
            /****
             * 组织签名参数
             */
            $signStr  = $signStr . '&detail_data=' . $this->params['detail_data'];
            $signStr  = $signStr . '&ext_param1='  . $this->params['ext_param1'];
            $signStr  = $signStr . '&key='         . $this->params['sign'];
            $signStr  = $signStr . '&notify_url='  . $this->params['notify_url'];
            $signStr  = $signStr . '&version=2';

            $sign='';
            $sign=md5($signStr);
            $detail_url .= "&sign=".strtolower($sign);
            $url = $url."?".$detail_url;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
            $signStr='';
            $signStr  = $signStr . 'agent_id='    . $this->params['agent_id'];
            //获取提交url
            $detail_url = $signStr;
            $detail_url .="&version=2";
            /****
             * 组织签名参数
             */
            $signStr  = $signStr . '&key='         . $this->params['sign'];
            $signStr  = $signStr . '&version=2';

            $sign='';
            $sign=md5($signStr);
            $detail_url .= "&sign=".strtolower($sign);
            $url = $url."?".$detail_url;

        } elseif ($this->type == 'query'){
            $url = $this->queryUrl;

            $signStr='';
            $signStr  = $signStr . 'agent_id='    . $this->params['agent_id'];
            $signStr  = $signStr . '&batch_no='   . $this->params['batch_no'];
            //获取提交url
            $detail_url = $signStr;
            $detail_url .="&version=2";
            /****
             * 组织签名参数
             */
            $signStr  = $signStr . '&key='         . $this->params['sign'];
            $signStr  = $signStr . '&version=2';

            $sign='';
            $sign=md5($signStr);
            $detail_url .= "&sign=".strtolower($sign);
            $url = $url."?".$detail_url;
        }

        $response = $this->client->request('GET', $url, [
            'verify' => false,
        ]);
        $returnStr = $response->getBody()->getContents();

        $resultxml=mb_convert_encoding($returnStr, "UTF-8", "GBK");
        $msgarray=Utils::toArray($resultxml);

        return $msgarray;
    }

    public function bankcodeht($bankname){
      $bank['中国工商银行']=1;
      $bank['中国建设银行']=2;
      $bank['中国农业银行']=3;
      $bank['中国邮政储蓄银行']=4;
      $bank['中国银行']=5;
      $bank['交通银行']=6;
      $bank['招商银行']=7;
      $bank['中国光大银行']=8;
      $bank['上海浦东发展银行']=9;
      $bank['华夏银行']=10;
      $bank['广发银行']=11;
      $bank['中信银行']=12;
      $bank['兴业银行']=13;
      $bank['中国民生银行']=14;
      $bank['杭州银行']=15;
      $bank['上海银行']=16;
      $bank['宁波银行']=17;
      $bank['平安银行']=18;

      return $bank[$bankname];
    }

}
