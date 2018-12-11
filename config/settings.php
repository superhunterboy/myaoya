<?php

$settings = [
    // set to false and ini_set('display_errors', 0) in production
    'displayErrorDetails'               => true,
    'determineRouteBeforeAppMiddleware' => true,
    'db'                                => [
        'driver'    => 'mysql',
        'host'      => '47.90.98.83',
        'port'      => '3306',
        'database'  => 'wmpay',
        'username'  => 'root',
        'password'  => '7Cf7_8fc870592749',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        // 'unix_socket' => '/tmp/mysql.sock',
        'prefix'    => '',
        'strict'    => false,
        'options'   => [
            \PDO::ATTR_PERSISTENT => false,
        ],
    ],
    'redis'                             => [
        [
            'host'  => '127.0.0.1',
            'port'  => '6379',
            'index' => 3,
        ],
    ],
    'key'                               => '35a7102186059dr8a1557f1e9c90ca47075d7c4e',
    'crawler'                           => [
        'host' => '47.90.98.83',
        'port' => '2345',
    ],
    'oss'                               => [
        'accessKeyId'     => 'LTAITVwqMcmO9FZn',
        'accessKeySecret' => 'xW3AjWhJxAP567FKv6rhg7axi0polo',
        'endpoint'        => 'oss-cn-hangzhou.aliyuncs.com',
        'bucket'          => 'wm2028',
    ],
    'upload'                            => __DIR__ . '/../public/uploadfiles',
    'host'                              => 'http://127.0.0.1',
];

$bankDictionaries = require __DIR__ . '/bankDictionaries.php';

$whelloteList = require __DIR__ . '/whelloteList.php';

$sms = require __DIR__ . '/sms.php';

return array_merge($settings, $bankDictionaries, $whelloteList, $sms);
