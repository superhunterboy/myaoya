<?php

require_once __DIR__ . '/../vendor/autoload.php';

$no = '1540794171486';
$key = 'e5b303780000a071005322365bd6a71detqqk62fs';
$notifyUrl='http://13.229.84.62/recharge/gaiya/notify';

// $callbackJson = '{"amount":"1000","goods_info":"CZ","merno":"312018042223067147","order_id":"201806271651089682316","orgid":"2220180022230663","plat_order_id":"2018062716510888173426","sign_data":"67dd001822141a114771ab606f142f3f","timestamp":"20180627170042","trade_date":"2018-06-27 16:59:42","trade_status":"0"}';

// var_dump(\Weiming\Libs\Payments\Bingo::getInstance([
//     'parterNo' => $no,
//     'parterKey' => $key,
//     'callbackUrl' => 'http://47.90.98.83:9898/payment/bingo/callback',
//     'notifyUrl' => 'http://47.90.98.83:9898/payment/bingo/notify'
// ])->verifySign(json_decode($callbackJson, true)));

$orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

echo \Weiming\Libs\Payments\Ylsl::getInstance([
    'parterNo' => $no,
    'parterKey' => $key,
    'callbackUrl' => 'http://47.90.98.83:9898/payment/gaiya/callback',
    'notifyUrl' => 'http://47.90.98.83:9898/payment/gaiya/notify'
])->signature('2', 10, $orderNo)->payment();

// var_dump(\Weiming\Libs\Utils::getBingoCanUseBalance(['parterNo' => $no, 'parterKey' => $key], 1));

// $orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));




// $orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

//print_r(\Weiming\Libs\AgencyPayments\Gaiya::getInstance(['parterNo' => $no, 'parterKey' => $key,'notifyUrl' => $notifyUrl])->generateSignature(['orderNo'  => $orderNo, 'acctId'   => '6214835304038928', 'acctName' => '潘荣远','bankCode'=>'03080000', 'tranAmt'  => 200], 'payment')->sendRequest());

//print_r(\Weiming\Libs\AgencyPayments\Gaiya::getInstance(['parterNo' => $no, 'parterKey' => $key,'notifyUrl' => $notifyUrl])->generateSignature(['orderNo'  => $orderNo, 'acctId'   => '6214835304038928', 'acctName' => '潘荣远','bankCode'=>'03080000', 'tranAmt'  => 200], 'payment')->sendRequest());


//\Weiming\Libs\AgencyPayments\Gaiya::getInstance(['parterNo' => $no, 'parterKey' => $key,'notifyUrl' => $notifyUrl])->generateSignature(['orderNo'  => $orderNo], 'balanceQuery')->sendRequest();
 //print_r(\Weiming\Libs\AgencyPayments\Gaiya::getInstance(['parterNo' => $no, 'parterKey' => $key])->generateSignature(['orderNo'  => '201810251628587995492'], 'query')->sendRequest());
