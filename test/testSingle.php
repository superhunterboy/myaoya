<?php
include('../vendor/autoload.php');

use Weiming\Libs\Payments\SingleQrPay;

for ($i=0; $i < 100; $i++) {
    $maoney = mt_rand(1,100)*10;
    $order = date('YmdHis') . mt_rand(10000,99999);
    $result = SingleQrPay::getInstance(['parterNo'=>'860000010000226', 'parterKey'=>'cae90ccb3d18486386a58c8e54bc3f63', 'callbackUrl'=>'http://47.90.98.83/payment/singleqr/callback'])->signature('7', $maoney, $order)->payment();

    echo $result . PHP_EOL;
}
