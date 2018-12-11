<?php

require_once __DIR__ . '/../vendor/autoload.php';

$key = 'w19K29904AA5rv26L385M9oy8k04N5t0aWQs92V5f37k3280A735UM743ovU';

 $orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

// echo \Weiming\Libs\Payments\Yibao::getInstance([
//     'parterNo' => '10022600418',
//     'parterKey' => $key,
//     'callbackUrl' => 'http://47.90.98.83:9898/payment/bingo/callback',
//     'notifyUrl' => 'http://47.90.98.83:9898/payment/bingo/notify'
// ])->signature('CMBCHINA-NET-B2C', 50, $orderNo)->payment();


 print_r(\Weiming\Libs\AgencyPayments\Yibao::getInstance(['parterNo' => '10022600418', 'parterKey' => $key])->generateSignature(['orderNo'  => $orderNo, 'acctId'   => '6226096524880818', 'acctName' => '张三', 'tranAmt'  => 1, 'bankCode' => 'CMBCHINA'], 'payment')->sendRequest());

$orderNo = '201807061820500766525';

//print_r(\Weiming\Libs\AgencyPayments\Yibao::getInstance(['parterNo' => '10022600418', 'parterKey' => $key])->generateSignature(['orderNo'  => $orderNo], 'query')->sendRequest());
// print_r(\Weiming\Libs\AgencyPayments\Yibao::getInstance(['parterNo' => '10022600418', 'parterKey' => $key])->generateSignature([], 'balanceQuery')->sendRequest());
