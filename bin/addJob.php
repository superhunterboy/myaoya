<?php

/**
 * 仅仅测试用
 * md5(md5(orderNo=201709091647365074836&account=asd1323123&fee=1.00&rechargeTime=2017-08-11 13:03:08) + 35a7102186059dr8a1557f1e9c90ca47075d7c4e) === sign
 */

require_once __DIR__ . '/../vendor/autoload.php';

// 项目配置文件
$settings = require_once __DIR__ . '/../config/settings.php';

Resque::setBackend($settings['redis'], 3);

// for ($i = 0; $i <= 0; $i++) {
//     $requestParams = [
//         'account'      => 'asd1323123_' . $i,
//         'fee'          => '1.00',
//         'orderNo'      => '201709091647365074836',
//         'rechargeTime' => '2017-08-11 13:03:08',
//         'sign'         => '7652a51f4c33713462821b8435f3f8f2',
//     ];
//     $token = Resque::enqueue(
//         'default',
//         \Weiming\Jobs\TestJob::class,
//         [
//             'rechargeUrl'   => 'http://47.52.107.34:66668/charge',
//             'requestParams' => $requestParams,
//         ],
//         true
//     );
//     echo $token . "\n";
// }

 //$token = Resque::enqueue('crawler', \Weiming\Jobs\AutoPayOutJob::class, ['orderNo' => '201807060143471483159', 'tranAmt' => 100, 'acctName' => '张三', 'acctId' => '6226096524880818', 'mobile' => '15826588888', 'bankName' => '工商银行'], true);
 //echo $token . "\r\n";
// Resque::dequeue('default');

// $token = Resque::enqueue('payoutChecker', \Weiming\Jobs\AutoPayOutCheckJob::class, ['orderNo' => '201712291843464684346'], true);
// echo $token . "\r\n";

// var_dump(Resque::queues());

// Resque::dequeue('default');
// Resque::dequeue('payoutChecker');

// $status = new Resque_Job_Status("641b274224dcb4b62ff325b957a025a61");
// var_dump($status->get());
// echo Resque_Job_Status::STATUS_WAITING . "\n";
// echo Resque_Job_Status::STATUS_RUNNING . "\n";
// echo Resque_Job_Status::STATUS_FAILED . "\n";
// echo Resque_Job_Status::STATUS_COMPLETE . "\n";
