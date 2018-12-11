<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Weiming\Libs\Utils;

$parterNo = '888950';
$parterKey = '55e912b6f1b149ec8c9db9bc3a4ed535';

// $orderNo = Utils::getOrderId(date('YmdHis'));

// var_dump(Weiming\Libs\Payments\Hengtong::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey, 'callbackUrl' => 'http://47.90.98.83:9898/callback', 'notifyUrl' => 'http://47.90.98.83:9898/notify'])->signature('1003', 1000, $orderNo)->payment());

// callback

$callbackJson = '{"orderid":"201806061757213825806","result":"1","amount":"97.91","systemorderid":"Q180606175721457551980289","completetime":"20180606175807","notifytime":"20180606180507","sign":"dc473fac12ac3c623b31133cf2f89ce6","attach":"CZ","sourceamount":"100.00","payamount":"99.40"}';

$datas = json_decode($callbackJson, true);

var_dump(Weiming\Libs\Payments\Hengtong::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey, 'callbackUrl' => 'http://47.90.98.83:9898', 'notifyUrl' => ''])->verifySign($datas));

