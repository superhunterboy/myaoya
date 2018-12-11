<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo  = '10000080001845';
$parterKey = 'f4196b32b36723a7b5701cf642fa1be8';

$orderNo = date('YmdHis') . mt_rand(100, 999);

$response = Weiming\Libs\Payments\Shunxin::getInstance([
    'parterNo'    => $parterNo,
    'parterKey'   => $parterKey,
    'callbackUrl' => 'http://157a.com',
    'notifyUrl'   => '',
])->signature('ABC', 2, $orderNo)->payment();

var_dump($response);

// var_dump($orderNo);

// $response = Weiming\Libs\AgencyPayments\Shunxin::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => $orderNo,
//     'bankCode' => 'ABC',
//     'acctId'   => '6228480616720139172', // '6228480616720139172',
//     'acctName' => '吴俊豪', // '吴俊豪',
//     'mobile'   => '13500000000',
//     'bankName' => '中国农业银行', // '中国农业银行',
//     'tranAmt'  => 100, // 分
//     'province' => '广东',
//     'city'     => '江门',
//     'branch'   => '江门泰富支行',
// ], 'payment')->sendRequest();

// var_dump($response);

// echo "============================================================\n";

// $response = Weiming\Libs\AgencyPayments\Shunxin::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => '20180131070517483',
// ], 'query')->sendRequest();

// var_dump($response);

// echo "============================================================\n";

// $response = Weiming\Libs\AgencyPayments\Shunxin::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([], 'balanceQuery')->sendRequest();

// var_dump($response);
