<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo  = '100000000002565';
$parterKey = 'ad6971e0833068af0ceg52bc4a7efe2cg6051gd8a4b753b9a9ff35c2bb5df3b4';

// $orderNo = date('YmdHis') . mt_rand(100, 999);

// $response = Weiming\Libs\AgencyPayments\Xifu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => $orderNo,
//     'bankCode' => '',
//     'acctId'   => '6226096554880818', // '6228480616720139172',
//     'acctName' => '冷春', // '吴俊豪',
//     'mobile'   => '13500000000',
//     'bankName' => '招商银行', // '农业银行',
//     'tranAmt'  => 100, // 分
//     'province' => '广东',
//     'city'     => '江门',
//     'branch'   => '江门泰富支行',
// ], 'payment')->sendRequest();

// var_dump($orderNo, $response);

// echo "============================================================\n";

$response = Weiming\Libs\AgencyPayments\Xifu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
    'orderNo'  => '20180128080827548',
], 'query')->sendRequest();

var_dump($response);

// $response = Weiming\Libs\AgencyPayments\Xifu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([], 'balanceQuery')->sendRequest();

// var_dump($response);
