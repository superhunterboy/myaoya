<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Weiming\Libs\AgencyPayments\Qingying;

$key = '{"field041":"88000277","parterKey":"eynnh9944h3f951r","field031":"0"}';

$args = [
    'orderNo'  => date('YmdHis').mt_rand(100, 999),
    'tranAmt'  => 100,
    'acctName' => 'admin',
    'acctId'   => 6225880139481111,
    'mobile'   => '',
    'bankName' => '华夏银行',
    'province' => 'guangdong',
    'city'     => 'shenzhen',
];

/*
                'txcode'   => 'F60012', //交易代码
                'txdate'   => date('Ymd'), //交易日期
                'txtime'   => date('His'), //交易时间
                'version'  => $this->version, //版本号
                'field003' => '000000', //处理码
                //'field011' => date('md') . mt_rand(10, 99), //流水号
                'field041' => $this->field041, //客户号
                'field042' => $this->field042, //商户号
                'field062' => $orderNo, //平台代付订单号
                'field125' => $this->field042 . microtime(true) * 10000, //SESSION

*/

$payOutObj=Qingying::getInstance([
    'parterNo' => '100013900000026',
    'parterKey' => $key,
]);


//balanceQuery
//payment




$result = $payOutObj->generateSignature($args, 'payment')->sendRequest();

print_r($result);