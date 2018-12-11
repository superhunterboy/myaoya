<?php

$logger = new \Monolog\Logger('WM_QUEUE_Logger');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/queue-' . date('Ymd') . '.log'));

// 队列任务失败触发此事件
Resque_Event::listen('onFailure', ['Weiming\Events\AutoRechargeEvent', 'onFailure']);
