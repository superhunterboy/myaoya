<?php

require_once __DIR__ . '/../vendor/autoload.php';

// $zhongdian = Weiming\Libs\Payments\Zhongdian::getInstance([
//     'parterNo'    => '3241237743963183193',
//     'parterKey'   => '26c18084a6344cfd8c9bb036fa047249',
//     'callbackUrl' => 'http://157a.com',
//     'notifyUrl'   => '',
// ])->signature('weixin', 4, date('YmdHis'))->payment();

// var_dump($zhongdian);

// echo "============================================================\n";

// $response = Weiming\Libs\AgencyPayments\Zhongdian::getInstance(['parterNo' => '863234534651', 'parterKey' => 'ab101c97353837e54dde2d32a5054acd'])->generateSignature([
//     'orderNo'  => date('YmdHis') . mt_rand(100, 999),
//     'acctId'   => '6236682940002696199',
//     'acctName' => '刘许为',
//     'mobile'   => '13577890000',
//     'tranAmt'  => 200,
// ], 'payment')->sendRequest();

// var_dump($response);

// echo "============================================================\n";

$response = Weiming\Libs\AgencyPayments\Zesheng::getInstance(['parterNo' => '1000002004', 'parterKey' => '285cG7T7210541abA2e66aREb0b73790b'])->generateSignature([
    'orderNo'  => '201801081740135154013',
], 'query')->sendRequest();

var_dump($response);

$response = Weiming\Libs\AgencyPayments\Zesheng::getInstance(['parterNo' => '1000002004', 'parterKey' => '285cG7T7210541abA2e66aREb0b73790b'])->generateSignature([
    'orderNo'  => '201801081733316343331',
], 'query')->sendRequest();

var_dump($response);
