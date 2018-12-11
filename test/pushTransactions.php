<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Weiming\Models\Pay;
use Weiming\Models\PayOut;
if($argc<2)
{
    die("参数缺失\n");
}
if(!in_array($argv[1], ['withdrawal','deposit']))
{
    die("参数错误\n");
}

$settings = require __DIR__ . '/../config/settings.php';
$app       = new \Slim\App(["settings" => $settings]);
$container = $app->getContainer();
// 注入eloquent orm
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['db'] = function ($container) use ($capsule) {
    return $capsule;
};


$url = 'http://finance.nyjt88.com/api/v1/transactions';
$accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjhlNGI0NTI0ZTUwOTVkNTdiN2UxMmY0YzA5ZmM1YTRmODYyNzZhMTBkNTU4YTNhOWRjYzEzMWM2MDU3OTNkYTMzZDJhN2QwY2Y3ZGUxZmI0In0.eyJhdWQiOiIzIiwianRpIjoiOGU0YjQ1MjRlNTA5NWQ1N2I3ZTEyZjRjMDlmYzVhNGY4NjI3NmExMGQ1NThhM2E5ZGNjMTMxYzYwNTc5M2RhMzNkMmE3ZDBjZjdkZTFmYjQiLCJpYXQiOjE1NDI2MDI5MzMsIm5iZiI6MTU0MjYwMjkzMywiZXhwIjoxNTc0MTM4OTMzLCJzdWIiOiI2Iiwic2NvcGVzIjpbXX0.Ikz9XHYzy_e-8TcveA_y2o9flGn_63dhdYFcFvl6urQiOE6SvudKYjqizlEN-thxUar-et7Ak5xXOp2K6QI-L30FiTM4yHVbijJtKb_qut4SjHQyaC2MJnmcEDl3p5zfqSMEeovRN_WCkOSIIK5XwFgUvKqUbU_Ilk8-pwiiSMKJfbQNQwjpFcLspU4rt1M5QhVf-9pCp3Z4o2dfnS7GNl5ukEJr2rBMFz1jaVZ4oeYFiFCW069FDuWJL7hlXNR3swxJQqTJruHf2uPZdQ2bQAYB8lNKI7uGcxqYeMVR28J2zz9SoQI6CbC9jJArFd8Q9PieSiPr-VZfDS7lO53lXWXCDHyXZYiAOXmx5dziykqRCuo4rtn8wIn3XmwgYHDF3Q4hMV0_QCpQ8qXXOgH4-sSKXRvVgMf_Dm0hqjWwD197HZPPWqQFqfZVP734TiHhGzJ_yUdHs2_TK4fn71nUw_rHViNVH5t9K1phSocsSHMu2tZbkoOsjKaAnx2bWqwkHLtGvQZi3Zyxeb3MwDBm5c-r8miubeMPCa2_nNKKukT0j3EaDRQxZgZ70ixhgiPjl0jQe2-jOXprnjkZOTeIdYUfBHDV6XvBowaRq9l46Z3QwBfOLlb0_hVq7OPfdD_Oy--J6gd7Cn9hRnDuMgu036kyf8TXPEuGl_4yP-eVrUo';
$offset = 0;
$nums = 50;
$startTIme = '2018-11-01 00:00:00';
if($argv[1] == 'withdrawal')
{
    $withdrawalFile = __DIR__ . '/../logs/withdrawal.txt';
    if(file_exists($withdrawalFile))
    {
        $offset = file_get_contents($withdrawalFile);
    }
    $res = PayOut::where('platform_status',  1)->where('created_at', '>=', $startTIme)->offset($offset)->limit($nums)->get(['id','account','amount','created_at','updated_at']);
    if(count($res))
    {
        file_put_contents($withdrawalFile,$offset+$nums);
    }
}
else if ($argv[1] == 'deposit')
{
    $depositFile = __DIR__ . '/../logs/deposit.txt';
    if(file_exists($depositFile))
    {
        $offset = file_get_contents($depositFile);
    }
    $res = Pay::where('status',  0)->where('created_at', '>=', $startTIme)->offset($offset)->limit($nums)->get(['id','user','money','created_at','updated_at']);
    if(count($res))
    {
        file_put_contents($depositFile,$offset+$nums);
    }
}

$postArr = [
    'tags'                             => 116,         // 支付-第三方费
    'transactions[0][description]'     => '',
    'transactions[0][currency_code]'   => 'RMB',
    'transactions[0][category_name]'   => '澳亚运营',   // 部门名称
];
$client = new Client();
foreach ($res as $one)
{
    if($argv[1] == 'withdrawal')
    {
        $postArr['type'] = 'withdrawal';
        $postArr['description'] = '澳亚'.substr($one->account,0,4).'***在'.date('Y-m-d H:i:s',strtotime($one->created_at)).'提现';
        $postArr['transactions[0][source_name]'] = '人民币';        // Asset account
        $postArr['transactions[0][destination_name]'] = '澳亚';    // Expense account
        $postArr['date'] = date('Y-m-d',strtotime($one->created_at));
        $postArr['payment_date'] = date('Y-m-d',strtotime($one->updated_at));
        $postArr['transactions[0][amount]'] = $one->amount;
    }
    else if ($argv[1] == 'deposit')
    {
        $postArr['type'] = 'deposit';
        $postArr['description'] = '澳亚'.substr($one->user,0,4).'***在'.date('Y-m-d H:i:s',strtotime($one->created_at)).'充值';
        $postArr['transactions[0][source_name]'] = '澳亚';         // Revenue account
        $postArr['transactions[0][destination_name]'] = '人民币';  // Asset account
        $postArr['date'] = date('Y-m-d',strtotime($one->created_at));
        $postArr['payment_date'] = date('Y-m-d',strtotime($one->updated_at));
        $postArr['transactions[0][amount]'] = $one->money;
    }
    //echo "<pre/>";print_r( $postArr );die;
    try{
        $res    = $client->request('POST', $url,
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$accessToken,
                ],
                'form_params' => $postArr
            ]
        );
        if ($res->getStatusCode() == '200') {
            $resData = $res->getBody()->getContents();
            if ($resData) {
                $returnArr = json_decode($resData, true);
                if(isset($returnArr['data'])){
                    echo "success\n";
                }
                else
                {
                    echo $one->id."=failed\n";
                }
            }
        }else{
            echo '请求错误!';
        }
    }
    catch (Exception $e)
    {
        if($argv[1] == 'withdrawal')
        {
            $withdrawalFailed = __DIR__ . '/../logs/withdrawalFailed.txt';
            file_put_contents($withdrawalFailed,$one->id."\n",FILE_APPEND);
        }
        elseif($argv[1] == 'deposit')
        {
            $depositFailed = __DIR__ . '/../logs/depositFailed.txt';
            file_put_contents($depositFailed,$one->id."\n",FILE_APPEND);
        }

    }
}

