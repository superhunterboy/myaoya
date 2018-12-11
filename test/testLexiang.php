<?php

require_once __DIR__ . '/../vendor/autoload.php';

$no = '210013';
$key = '5e9047669424cdc4b3e8cad1c061e302';

$callbackJson = '{"platform_trade_no":"A20180706180837132311","orderid":"201807061808367471656","price":"50.00","realprice":"50.00","orderuid":"","attach":"","key":"12170e719d99b7587f6db8619c14d766"}';

var_dump(\Weiming\Libs\Payments\Lexiang::getInstance([
    'parterNo' => $no,
    'parterKey' => $key,
    'callbackUrl' => 'http://47.90.98.83:9898/payment/bingo/callback',
    'notifyUrl' => 'http://47.90.98.83:9898/payment/bingo/notify'
])->verifySign(json_decode($callbackJson, true)));

// $orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

// echo \Weiming\Libs\Payments\Lexiang::getInstance([
//     'parterNo' => $no,
//     'parterKey' => $key,
//     'callbackUrl' => 'http://47.90.98.83:9898/payment/bingo/callback',
//     'notifyUrl' => 'http://47.90.98.83:9898/payment/bingo/notify'
// ])->signature(1, 50, $orderNo)->payment();
