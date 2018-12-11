<?php

require_once __DIR__ . '/../vendor/autoload.php';

$currentTime = date('YmdHis');

$response = Weiming\Libs\AgencyPayments\KaiLianTong::getInstance(['parterNo' => '100020091219001', 'parterKey' => '{"pubKey":"ops-test.cer","priKey":"test-rsa.pfx","saltKey":"123456"}'])->generateSignature(['orderNo'  => Weiming\Libs\Utils::getOrderId($currentTime), 'acctId'   => '88881234567', 'acctName' => '张三', 'bankName' => '虚拟银行上海分行金桥支行', 'tranAmt'  => '100'], 'payment')->sendRequest();

// $response = Weiming\Libs\AgencyPayments\KaiLianTong::getInstance(['parterNo' => '100020091219001', 'parterKey' => ''])->generateSignature(['orderNo'  => '201712221050198755019'], 'query')->sendRequest();

// $response = Weiming\Libs\AgencyPayments\KaiLianTong::getInstance(['parterNo' => '100020091219001', 'parterKey' => ''])->generateSignature([], 'balanceQuery')->sendRequest();

var_dump($response);
