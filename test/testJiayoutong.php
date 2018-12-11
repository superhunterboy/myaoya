<?php

require_once __DIR__ . '/../vendor/autoload.php';
$parterNo = '110046';
$parterKey = '{"pubKey":"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwmurssqsSTIa7RlahkKIB062oMqNzeCma0JGoTSHVtWlr0c8CPvw+cXvKbRAemiN7lCpnYSpUSFTexBm1+vyQ9cmfo9AH5SPHjVAgUPErhF/8Y+Th3QNr71gJHUkM6r3iGjtie7VGbG34YL42vjiM/KTAR875Zji4NKpXGaaYKQIDAQAB","priKey":"MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAPzeJLECNJW3IYL7acM70IP6UnEp+EKOAwu05ypIwY3lnlgC93tC6ndGck1svJcAjtTGxGbyiAfKq8FHfPiaM5Awq0mIavH6u1ZvMYUY67cqeYm48aTV/lzbhjEma8ER/723JMRFFoENTDnN/loKbtLDFtTGieYrTdbSTzfrq8RBAgMBAAECgYA7QdubV4b3JslFpqEsTVIZ+4liV5GEFz+DeMtLT218UTkAKwHP8uh7NxkGJ9syD8Fo/4N7FI7oNIh3bMdOguReCKo4Wrq4+zXtRCP6t0bupBTrhiqrfGNGqqCOSp11AoDSSmfs+8EquF5jacewCKcL8yDvC0jX/RsX4NdP5htKeQJBAP52ScImHQCd57zE6Hg698k0m3fsmmUgcOKAeEFNCLXEo/Y0u1ph0I64SOxJPRclVLcaAKf1MAEXcWq9SlZsMCcCQQD+ZWNwNNWtn4qZd4hFUVYmPE2D0z12lphL+r0kiE6MEr+wKkkyerURx1FkNMQiEz3vgKSTiAWkizDVB/bk1sFXAkEA7LrLP84JaUt+DgX6Z/AkGZHRmS4LLU2S94WhWs757XC1QRYpvpoFVuFnJju0k9XtDDMqrRbTxBA1OaJEiEY6PQJBALMZOC+X0tOAXbh7seFzSN50V/I19rCVhzT+msisefgdfvyMWKoEP5P7qi6NA4Ql3dXO0I7ZFXfpUoC4MbacBtMCQAfa3hVxXY5LL2hyIYjQ2S2kC3t4xZOpnHXiTZdpj08kdXnTv2nZFHFzXNVps6a/mx/tleEzVP5z4lu4Kw6lvtI="}';

$orderNo = \Weiming\Libs\Utils::getOrderId(date('YmdHis'));

echo \Weiming\Libs\AgencyPayments\Jiayoutong::getInstance([
    'parterNo' => $parterNo,
    'parterKey' => $parterKey,
])->generateSignature(['orderNo' => $orderNo, 'acctId' => '6226096524880818', 'acctName' => '张三', 'tranAmt' => 100])->sendRequest();
die;
echo \Weiming\Libs\AgencyPayments\Jiayoutong::getInstance([
    'parterNo'  => $parterNo,
    'parterKey'   => $parterKey
])->generateSignature(['merNo'  => $parterNo,'orderNo'  => '20180426164100737'], 'query')->sendRequest();


