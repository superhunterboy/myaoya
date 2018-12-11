<?php

require_once __DIR__ . '/../vendor/autoload.php';

$no = '5720180004140799';
$key = '{"merno":"042018120414078824","md5Key":"PA5bEiEaBcBwRGasDJXkRHrYkthHckn3","desKey":"D52P2xNCct7hk8HEmXhjZKKWa7tNmNPr"}';

// $callbackJson = '{"amount":"1000","goods_info":"CZ","merno":"312018042223067147","order_id":"201806271651089682316","orgid":"2220180022230663","plat_order_id":"2018062716510888173426","sign_data":"67dd001822141a114771ab606f142f3f","timestamp":"20180627170042","trade_date":"2018-06-27 16:59:42","trade_status":"0"}';

// var_dump(\Weiming\Libs\Payments\Bingo::getInstance([
//     'parterNo' => $no,
//     'parterKey' => $key,
//     'callbackUrl' => 'http://47.90.98.83:9898/payment/bingo/callback',
//     'notifyUrl' => 'http://47.90.98.83:9898/payment/bingo/notify'
// ])->verifySign(json_decode($callbackJson, true)));

$orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

echo \Weiming\Libs\Payments\Bingo::getInstance([
    'parterNo' => $no,
    'parterKey' => $key,
    'callbackUrl' => 'http://47.90.98.83:9898/payment/bingo/callback',
    'notifyUrl' => 'http://47.90.98.83:9898/payment/bingo/notify'
])->signature('0203', 100, $orderNo)->payment();

// var_dump(\Weiming\Libs\Utils::getBingoCanUseBalance(['parterNo' => $no, 'parterKey' => $key], 1));

// $orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

// var_dump(\Weiming\Libs\AgencyPayments\Bingo::getInstance(['parterNo' => $no, 'parterKey' => $key])->generateSignature(['orderNo'  => $orderNo], 'balanceQuery')->sendRequest());


// $orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

// print_r(\Weiming\Libs\AgencyPayments\Bingo::getInstance(['parterNo' => $no, 'parterKey' => $key])->generateSignature(['orderNo'  => $orderNo, 'accountCode' => '958', 'bankName' => '招商银行', 'bankCode' => '308', 'acctId'   => '6226096524880818', 'acctName' => '张三', 'tranAmt'  => 1], 'payment')->sendRequest());


// \Weiming\Libs\AgencyPayments\Bingo::getInstance(['parterNo' => $no, 'parterKey' => $key])->generateSignature(['orderNo'  => '201806261416537264430'], 'query')->sendRequest();
