<?php

namespace Weiming\Libs\AgencyPayments;

use GuzzleHttp\Client;
use Weiming\Libs\Utils;

class Yibao
{
    private $gateway         = 'https://cha.yeepay.com/app-merchant-proxy/groupTransferController.action';
    private $queryUrl        = 'https://cha.yeepay.com/app-merchant-proxy/groupTransferController.action';
    private $balanceQueryUrl = 'https://cha.yeepay.com/app-merchant-proxy/transferController.action';

    private $parterKey;
    private $parterNo;
    private $orderNo;
    private $type;
    private $params = '';
    private $port = 8288;//默认读取的端口号

    private static $instance = null;

    private function __construct($config)
    {
        $this->client = new Client([
            'http_errors' => false,
            'timeout'     => 30,
        ]);
        $this->parterNo  = $config['parterNo'];
        $this->parterKey = $config['parterKey'];
        if ($this->parterNo == '10022993221') { //如果是新开的易宝商户号
            $this->port = 8289;
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
        $signArr      = [];

        if ($this->type == 'payment') {
            $businessData = [
                'cmd'              => 'TransferSingle',
                'version'          => '1.1',
                'group_Id'         => $this->parterNo,
                'mer_Id'           => $this->parterNo,
                // 'product'          => 'RJT',
                'batch_No'         => substr($this->orderNo, 1),
                'bank_Code'        => $payInfo['bankCode'],
                'order_Id'         => $this->orderNo,
                // 'cnaps'            => '',
                // 'bank_Name'        => '',
                'branch_Bank_Name' => '',
                'amount'           => sprintf("%.2f", $payInfo['tranAmt'] / 100), // 元
                'account_Name'     => $payInfo['acctName'],
                'account_Number'   => $payInfo['acctId'],
                // 'account_Type'     => '',
                'province'         => '',
                'city'             => '',
                'fee_Type'         => 'SOURCE',
                // 'payee_Email'      => '',
                // 'payee_Mobile'     => '',
                'urgency'          => 1,
                // 'leave_Word'       => '',
                // 'abstractInfo'     => '',
                // 'remarksInfo'      => '',
                'hmac'             => '',
            ];

            $signArr = [
                'cmd'            => $businessData['cmd'],
                'mer_Id'         => $businessData['mer_Id'],
                'batch_No'       => $businessData['batch_No'],
                'order_Id'       => $businessData['order_Id'],
                'amount'         => $businessData['amount'],
                'account_Number' => $businessData['account_Number'],
            ];
        } elseif ($this->type == 'query') {
            $businessData = [
                'cmd'        => 'BatchDetailQuery',
                'version'    => '1.0',
                'group_Id'   => $this->parterNo,
                'mer_Id'     => $this->parterNo,
                'query_Mode' => 1,
                // 'product'    => 'RJT',
                'batch_No'   => substr($this->orderNo, 1),
                'order_Id'   => $this->orderNo,
                'page_No'    => 1,
                'date'       => date('Y-m-d'),
                'hmac'       => '',
            ];

            $signArr = [
                'cmd'      => $businessData['cmd'],
                'mer_Id'   => $businessData['mer_Id'],
                'batch_No' => $businessData['batch_No'],
                'order_Id' => $businessData['order_Id'],
                'page_No'  => $businessData['page_No'],
            ];
        } elseif ($this->type == 'balanceQuery') {
            $businessData = [
                'cmd'     => 'AccountBalanaceQuery',
                'version' => '1.0',
                'mer_Id'  => $this->parterNo,
                'date'    => date('Y-m-d'),
                'hmac'    => '',
            ];

            $signArr = [
                'cmd'    => $businessData['cmd'],
                'mer_Id' => $businessData['mer_Id'],
                'date'   => $businessData['date'],
            ];
        }

        $businessData['hmac'] = $this->getSignStr($signArr);

        $this->params = Utils::arrToXml($businessData);

        return $this;
    }

    public function sendRequest()
    {
        // echo $this->params . PHP_EOL;
        $url = $this->gateway;
        if ($this->type == 'query') {
            $url = $this->queryUrl;
        } elseif ($this->type == 'balanceQuery') {
            $url = $this->balanceQueryUrl;
        }
        $response = $this->client->request('POST', $url, [
            'body'   => $this->params,
            'verify' => false,
        ]);
        if ($response->getStatusCode() == '200') {
            $returnStr = $response->getBody()->getContents();
            // $returnStr = mb_convert_encoding($returnStr, 'utf-8', 'gbk');
            // 下单
            /**
             * <?xml version="1.0" encoding="GBK"?>
             * <data>
             *     <cmd>TransferSingle</cmd>
             *     <ret_Code>1</ret_Code>
             *     <order_Id>201807052125063109360</order_Id>
             *     <r1_Code>0025</r1_Code>
             *     <bank_Status>I</bank_Status>
             *     <error_Msg></error_Msg>
             *     <hmac></hmac>
             * </data>
             */

            // 查询
            /**
             * <?xml version="1.0" encoding="GBK"?>
             * <data>
             *     <cmd>BatchDetailQuery</cmd>
             *     <ret_Code>1</ret_Code>
             *     <batch_No>20180705212506310936</batch_No>
             *     <total_Num>1</total_Num>
             *     <end_Flag>Y</end_Flag>
             *     <list>
             *         <items>
             *             <item>
             *                 <order_Id>201807052125063109360</order_Id>
             *                 <payee_Bank_Account>6226096524880818</payee_Bank_Account>
             *                 <remarksInfo></remarksInfo>
             *                 <refund_Date></refund_Date>
             *                 <real_pay_amount>0.01</real_pay_amount>
             *                 <payee_BankName>招商银行</payee_BankName>
             *                 <complete_Date>2018-07-05 21:25:09</complete_Date>
             *                 <request_Date>2018-07-05 21:25:09</request_Date>
             *                 <amount>0.01</amount>
             *                 <fee>0.0</fee>
             *                 <payee_Name>张三</payee_Name>
             *                 <abstractInfo></abstractInfo>
             *                 <bank_Status>W</bank_Status>
             *                 <r1_Code>0028</r1_Code>
             *                 <fail_Desc>账务异常:系统拦截</fail_Desc>
             *                 <note></note>
             *             </item>
             *         </items>
             *     </list>
             *     <hmac></hmac>
             * </data>
             */

            // 余额查询
            /**
             * <?xml version="1.0" encoding="GBK"?>
             * <data>
             *     <cmd>AccountBalanaceQuery</cmd>
             *     <ret_Code>1</ret_Code>
             *     <balance_Amount>1.01</balance_Amount>
             *     <valid_Amount>1.01</valid_Amount>
             *     <hmac></hmac>
             * </data>
             */

            $returnStr = Utils::xmlToArr($returnStr);

            if ($this->verifySign($returnStr)) {

                return $this->moreTone($returnStr);

            }

            return null;
        }

        return null;
    }

    private function verifySign($params = [])
    {
        // 代付下单验签： cmd、ret_Code、r1_Code
        // 代付单查询验签： cmd、ret_Code、batch_No、total_Num、end_Flag
        // 余额查询验签： cmd、ret_Code、balance_Amount、valid_Amount

        $signFields = [];

        if ($this->type == 'query') {

            $signFields = ['cmd', 'ret_Code', 'batch_No', 'total_Num', 'end_Flag'];

        } elseif ($this->type == 'balanceQuery') {

            $signFields = ['cmd', 'ret_Code', 'balance_Amount', 'valid_Amount'];

        } elseif ($this->type == 'payment') {

            $signFields = ['cmd', 'ret_Code', 'r1_Code'];

        }

        $signStr = $this->getVerifyStr($params, $signFields);

        return 'SUCCESS' == file_get_contents('http://127.0.0.1:'.$this->port.'/verify?req=' . $signStr . $this->parterKey . '&sign=' . urlencode($params['hmac']));
    }

    private function getSignStr($params = [])
    {
        $signStr = '';
        foreach ($params as $param) {
            $signStr .= $param;
        }
        return file_get_contents('http://127.0.0.1:'.$this->port.'/sign?req=' . $signStr . $this->parterKey);
    }

    private function getVerifyStr($params, $signFields)
    {
        $tmpStr = '';
        foreach ($params as $key => $param) {
            if (in_array($key, $signFields)) {
                $tmpStr .= $param;
            }
        }
        return $tmpStr;
    }

    /**
     * 多维数组转换为一维数组
     * @param $array  数组
     * @return array 一维数组
     */
    private function moreTone($arr)
    {
        $tmpArr = iterator_to_array(
            new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($arr)
            ),
            true
        );
        if (isset($tmpArr['hmac'])) {
            unset($tmpArr['hmac']);
        }
        return $tmpArr;
    }
}
