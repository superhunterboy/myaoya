<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Chuanhua
{
    private $gateway  = 'https://openapi.tf56.com/service/api'; //https://openapitest.tf56.com/service/api
    private $service_id;                    //接口在网关openapi中对应的id  服务名称
    private $appkey;                        //商户key
    private $appid;                         //传化支付分配给商户的appid
    private $tf_timestamp;                  //时间戳  时间戳格式：yyyyMMddHHmmss
    private $tf_sign;                       //签名字符串   生成签名时，需要加入key字段
    private $businessnumber;                //商户端的流水号，需保证在商户端不重复
    private $subject = '提现';            //商品名称
    private $transactionamount;             //交易金额  单位：元
    private $bankcardnumber;                //银行卡号码
    private $bankcardname;                  //银行卡姓名
    private $bankname;                      //银行名称(必填)
    private $bankcardtype = '个人';         //个人  银行卡类型：个人、企业
    private $bankaccounttype = '储蓄卡';    //银行卡借贷类型：储蓄卡、信用卡
    private $fromaccountnumber = '8800009862411';             //会员支付账户 8801111114253
    private $backurl;                       //回掉地址
    //查询余额
    private $accountnumber = '8800009862411';                 //传化支付账号

    private $params = [];
    private $optype;

    private static $instance = null;

    private function __construct($config)
    {
        $this->client       = new Client([
            'http_errors' => false,
            'timeout' => 30
        ]);
        $this->appid        = $config['parterNo'];
        $this->appkey       = $config['parterKey'];
        //$this->backurl      = $config['callbackUrl'];
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

    public function generateSignature($payInfo = [], $optype = 'payment')
    {
        $this->optype               = $optype;
        $this->businessnumber       = $payInfo['orderNo'] ?? '';
        $this->transactionamount    = $payInfo['tranAmt'] ?? '';
        $this->bankcardname         = $payInfo['acctName'] ?? '';
        $this->bankcardnumber       = $payInfo['acctId'] ?? '';
        $this->bankname             = $payInfo['bankName'] ?? '';
        $this->tf_timestamp         = date('YmdHis');
        //出款到银行卡
        if ($this->optype == 'payment') {
            $this->service_id = 'tf56enterprise.enterprise.payForCustomer'; //tf56enterprise
            $this->params = [
            'businessnumber'        => $this->businessnumber,           //商户端的流水号，需保证在商户端不重复
            'subject'               => $this->subject,                  //商品名称
            'transactionamount'     => $this->transactionamount / 100,        //交易金额  单位：元
            'bankcardnumber'        => $this->bankcardnumber,           //银行卡号码
            'bankcardname'          => $this->bankcardname,             //银行卡姓名
            'bankname'              => $this->bankname,                 //银行名称(必填)
            'bankcardtype'          => $this->bankcardtype,             //个人  银行卡类型：个人、企业
            'bankaccounttype'       => $this->bankaccounttype,          //银行卡借贷类型：储蓄卡、信用卡
            'fromaccountnumber'     => $this->fromaccountnumber,        //会员支付账户
            //'backurl'               => $this->backurl,                  //回掉地址
            ];
        }
        //查询余额
        if ($this->optype == 'balanceQuery') {
            $this->service_id = 'tf56pay.enterprise.queryEnterpriseAccountBanlance';
            $this->params['accountnumber'] = $this->accountnumber;
        }
        //查询代付结果
        if ($this->optype == 'query') {
            $this->service_id = 'tf56pay.enterprise.queryTradeStatus';
            $this->params['businessnumber'] = $this->businessnumber;
        }

        // 公共字段
        $this->params['service_id']        = $this->service_id;                //接口在网关openapi中对应的id  服务名称
        $this->params['appid']             = $this->appid;                     //传化支付分配给商户的appid
        $this->params['tf_timestamp']      = $this->tf_timestamp;              //时间戳  时间戳格式：yyyyMMddHHmmss
        $this->params['dog_sk']            = $this->appkey;                    //密钥

        $signStr                 = $this->getSignatureString($this->params);
        $this->tf_sign           = strtoupper(md5($signStr));
        $this->params['tf_sign'] = $this->tf_sign;
        unset($this->params['dog_sk']);

        return $this;
    }

    public function sendRequest()
    {
        $result = Utils::sendPost($this->gateway, http_build_query($this->params), 120, true);
        // return '请求参数:'.$this->gateway.'---'.urldecode(http_build_query($this->params)).'----签名字符串:'.$this->getSignatureString($this->params).'---签名结果:'.$this->tf_sign.'---结果:'.$result;
        if ($result) {
            $returnArr = json_decode($result, true);
            if ($returnArr['result'] == 'success') {
                /**
                 * 下单：
                 * {
                 *     "code":"",
                 *     "count":"1",
                 *     "data":"",
                 *     "msg":"代付[BN20171126105904564]提交 成功-->状态:[待处理]",
                 *     "result":"success"
                 * }
                 */
                return $returnArr;//['data'];

            }
            // 非 00 的情况，都按处理中处理，真实处理结果以反查接口返回的结果为准
            return ['state' => '01', 'orderId' => '', 'origData' => $returnArr];
        }
        return null;
    }

    /**
     * 得到待签名字符串
     * @param  array  $arr 签名数据
     * @return string
     */
    private function getSignatureString($arr)
    {
        $signPars = "";
        krsort($arr);
        foreach ($arr as  $v) {
            if ($v != '') {
                $signPars .= $v ;
            }
        }
        return $signPars;
    }

    /**
     * 签名验证
     * @param  array  $arr 接口返回参数
     * @param  string  $key 商户key
     * @return boolean
     */
    private function verifySignature($arr, $key)
    {
        $signPars = "";
        ksort($arr);
        foreach ($arr as $k => $v) {
            if ("sign" != $k && "errorMsg" != $k && "" !== $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "KEY=" . $key;
        $sign = strtoupper(md5($signPars));
        return $sign == strtoupper($arr['sign']);
    }
}
