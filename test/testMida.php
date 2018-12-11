<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parterNo  = '2018'; // '10052';
$parterKey = '6c67c5c22348ed7eeae92452959560c66636da26'; // 'a0fc187e2f7594847569f7d083fee0e29aa34f9d';

$orderNo = date('YmdHis') . mt_rand(100, 999);

$response = Weiming\Libs\Payments\Mida::getInstance([
    'parterNo'    => $parterNo,
    'parterKey'   => $parterKey,
    'callbackUrl' => 'http://157a.com',
    'notifyUrl'   => 'http://157a.com',
])->signature('ABC', 2, $orderNo)->payment();

var_dump($response);
