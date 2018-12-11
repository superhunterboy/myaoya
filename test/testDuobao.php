<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo = '989770';
$parterKey = 'b91986b70c1443bca68454723b8c92f3';

$orderNo = date('YmdHis') . mt_rand(100, 999);

var_dump(Weiming\Libs\Payments\Duobao::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey, 'callbackUrl' => 'http://47.90.98.83:9898', 'notifyUrl' => ''])->signature('963', 50000, $orderNo)->payment());

// $response = Weiming\Libs\AgencyPayments\Duobao::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => $orderNo,
//     'bankName' => '招商银行',
//     'acctId'   => '6228480616720139172', // '6228480616720139172',
//     'acctName' => '张三', // '吴俊豪',
//     'tranAmt'  => 300, // 分
// ], 'payment')->sendRequest();

// var_dump($orderNo, $response);

// echo "============================================================\n";

// $response = Weiming\Libs\AgencyPayments\Duobao::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => '20180426164100737',
// ], 'query')->sendRequest();

// print_r($response);

// $response = Weiming\Libs\AgencyPayments\Duobao::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([], 'balanceQuery')->sendRequest();

// var_dump($response);
