<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo = '910180123135430';
$parterKey = '9b60b5d0933c3c5d74ec0000e95dc622';

// $orderNo = date('YmdHis') . mt_rand(100, 999);

// $response = Weiming\Libs\AgencyPayments\Nongfu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => $orderNo,
//     'bankCode' => '01030000',
//     'acctId'   => '6228480616720139172', // '6228480616720139172',
//     'acctName' => '吴俊豪', // '吴俊豪',
//     'tranAmt'  => 100, // 分
// ], 'payment')->sendRequest();

// var_dump($orderNo, $response);

// echo "============================================================\n";

$response = Weiming\Libs\AgencyPayments\Nongfu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
    'orderNo'  => '20180129032935177',
], 'query')->sendRequest();

print_r($response);

// $response = Weiming\Libs\AgencyPayments\Nongfu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([], 'balanceQuery')->sendRequest();

// var_dump($response);
