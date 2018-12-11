<?php
//自动加载
require_once __DIR__ . '/../vendor/autoload.php';
// 项目配置文件
$settings = require_once __DIR__ . '/../config/settings.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
return true;
$task = new Worker();
// 开启多少个进程运行定时任务，注意业务是否在多进程有并发问题
$task->count = 1;

$task->onWorkerStart = function($task){
    // 每30分执行一次
    $time_interval = 60*30;
    Timer::add($time_interval, function()
    {
        global $settings;
        $url = $settings['host'] . '/autoJudgment';

        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); //返回原生内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //数据按字符串返回,不直接输出
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true); //设置跟踪页面的跳转
        curl_setopt ($ch, CURLOPT_AUTOREFERER, true); //设置跳转页面的referer
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 1);


        $result = curl_exec($ch);
        if (false === $result) {
            $result =  curl_error($ch);
        }
        file_put_contents(__DIR__ . '/../logs/' . date('Y-m-d').'-timing.log', date('Y-m-d H:i:s').'状态:'.$result.PHP_EOL, FILE_APPEND);
        echo $result;
    });
};

// 运行worker
Worker::runAll();