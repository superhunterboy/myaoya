<?php

// 项目配置文件
$settings = require_once __DIR__ . '/../config/settings.php';
// 队列名称，*为所有队列
putenv("QUEUE=*");
// Redis 地址端口
putenv("REDIS_BACKEND=" . $settings['redis'][0]['host'] . ":" . $settings['redis'][0]['port']);
// Redis DB
putenv("REDIS_BACKEND_DB=" . $settings['redis'][0]['index']);
// 详细日志
putenv("VVERBOSE=1");
// Worker 数量
putenv("COUNT=1");
// 检查时间间隔 5s
putenv("INTERVAL=5");
// pid 文件
putenv("PIDFILE=" . __DIR__ . '/resque.pid');
// 包含文件
putenv("APP_INCLUDE=" . __DIR__ . '/init.php');

// Find and initialize Composer
$files = array(
    __DIR__ . '/../vendor/autoload.php',
);

$found = false;
foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

$QUEUE = getenv('QUEUE');
if (empty($QUEUE)) {
    die("Set QUEUE env var containing the list of queues to work.\n");
}

/**
 * REDIS_BACKEND can have simple 'host:port' format or use a DSN-style format like this:
 * - redis://user:pass@host:port
 *
 * Note: the 'user' part of the DSN URI is required but is not used.
 */
$REDIS_BACKEND = getenv('REDIS_BACKEND');

// A redis database number
$REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
if (!empty($REDIS_BACKEND)) {
    if (empty($REDIS_BACKEND_DB)) {
        Resque::setBackend($REDIS_BACKEND);
    } else {
        Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB);
    }

}

$logLevel = false;
$LOGGING  = getenv('LOGGING');
$VERBOSE  = getenv('VERBOSE');
$VVERBOSE = getenv('VVERBOSE');
if (!empty($LOGGING) || !empty($VERBOSE)) {
    $logLevel = true;
} else if (!empty($VVERBOSE)) {
    $logLevel = true;
}

$APP_INCLUDE = getenv('APP_INCLUDE');
if ($APP_INCLUDE) {
    if (!file_exists($APP_INCLUDE)) {
        die('APP_INCLUDE (' . $APP_INCLUDE . ") does not exist.\n");
    }

    require_once $APP_INCLUDE;
}

// See if the APP_INCLUDE containes a logger object,
// If none exists, fallback to internal logger
if (!isset($logger) || !is_object($logger)) {
    $logger = new Resque_Log($logLevel);
}

$BLOCKING = getenv('BLOCKING') !== false;

$interval = 5;
$INTERVAL = getenv('INTERVAL');
if (!empty($INTERVAL)) {
    $interval = $INTERVAL;
}

$count = 1;
$COUNT = getenv('COUNT');
if (!empty($COUNT) && $COUNT > 1) {
    $count = $COUNT;
}

$PREFIX = getenv('PREFIX');
if (!empty($PREFIX)) {
    $logger->log(Psr\Log\LogLevel::INFO, 'Prefix set to {prefix}', array('prefix' => $PREFIX));
    Resque_Redis::prefix($PREFIX);
}

if ($count > 1) {
    for ($i = 0; $i < $count; ++$i) {
        $pid = Resque::fork();
        if ($pid === false || $pid === -1) {
            $logger->log(Psr\Log\LogLevel::EMERGENCY, 'Could not fork worker {count}', array('count' => $i));
            die();
        }
        // Child, start the worker
        else if (!$pid) {
            $queues = explode(',', $QUEUE);
            $worker = new Resque_Worker($queues);
            $worker->setLogger($logger);
            $logger->log(Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
            $worker->work($interval, $BLOCKING);
            break;
        }
    }
}
// Start a single worker
else {
    $queues = explode(',', $QUEUE);
    $worker = new Resque_Worker($queues);
    $worker->setLogger($logger);

    $PIDFILE = getenv('PIDFILE');
    if ($PIDFILE) {
        file_put_contents($PIDFILE, getmypid()) or
        die('Could not write PID information to ' . $PIDFILE);
    }

    $logger->log(Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
    $worker->work($interval, $BLOCKING);
}
