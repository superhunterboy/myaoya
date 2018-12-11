<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo = '144801006875';
$md5Key   = '67b7d54f-f020-11e7-9204-8b36320c9f02';
$payKey   = '579D3D73AFEED6D8AF0E54FB05825A9D72534D376433';

// $resArr = [
//     'result_code' => '000000',
//     'result_msg' => '代付申请成功，请耐心等待出款结果',
//     'merchant_no' => '144801006875',
//     'order_no' => '201801281713135989',
//     'mer_order_no' => '20180128091313597',
//     'result' => 'H',
// ];

// $sign = '48147beb9969a708b2f5107db7d8ecd2';

// $resArr = [
//     "result_code"  => "000000",
//     "result_msg"   => "代付订单信息查询成功",
//     "merchant_no"  => "144801006875",
//     "order_no"     => "20180126130623",
//     "result"       => "H",
//     "amount"       => "4",
//     "withdraw_fee" => "2",
// ];

// $sign = "358a52dd9d38f38b4f8577ae961d0555";

// $resArr = [
//     "result_code" => "000000",
//     "result_msg"  => "商户钱包余额查询成功",
//     "merchant_no" => "144801006875",
//     "balance"     => "15476",
// ];

// $sign = "c82855395505745a47f7b7b1d6301950";

// $jsonStr = json_encode($resArr, JSON_UNESCAPED_UNICODE);

// $md5Str = md5($jsonStr . $md5Key);

// var_dump($sign, $md5Str);

$parterKey = json_encode(['md5Key' => $md5Key, 'payKey' => $payKey]);

$orderNo = date('YmdHis') . mt_rand(100, 999);

$response = Weiming\Libs\AgencyPayments\Aifu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
    'orderNo'  => $orderNo,
    'bankCode' => 'ABC',
    'acctId'   => '6228480616720139172', // '6228480616720139172',
    'acctName' => '吴俊豪', // '吴俊豪',
    'mobile'   => '13500000000',
    'bankName' => '农业银行', // '农业银行',
    'tranAmt'  => 200, // 分
    'province' => '广东',
    'city'     => '江门',
    'branch'   => '',
], 'payment')->sendRequest();

var_dump($orderNo, $response);

// echo "============================================================\n";

// $response = Weiming\Libs\AgencyPayments\Aifu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([
//     'orderNo'  => '20180128080827548',
// ], 'query')->sendRequest();

// var_dump($response);

// $response = Weiming\Libs\AgencyPayments\Aifu::getInstance(['parterNo' => $parterNo, 'parterKey' => $parterKey])->generateSignature([], 'balanceQuery')->sendRequest();

// var_dump($response);
