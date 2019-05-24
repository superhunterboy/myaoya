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


$url = 'http://www.firefly.local/api/v1/transactions';
$accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjBlZmE3ZTE5ZTI4YzdlZjdiODNmYjFhNmY1NzkzYzJkYTg0NzViM2RmMDY2NmNmYjYzODJhMmM0MDlhMDE3OTQzZTk3M2E2YTU1MWNkN2IzIn0.eyJhdWQiOiIxIiwianRpIjoiMGVmYTdlMTllMjhjN2VmN2I4M2ZiMWE2ZjU3OTNjMmRhODQ3NWIzZGYwNjY2Y2ZiNjM4MmEyYzQwOWEwMTc5NDNlOTczYTZhNTUxY2Q3YjMiLCJpYXQiOjE1NTMwNTE0OTUsIm5iZiI6MTU1MzA1MTQ5NSwiZXhwIjoxNTg0NjczODk0LCJzdWIiOiIyIiwic2NvcGVzIjpbXX0.Pct8VJ7axpJJUlrb8ezzJgk-Pr5X8E9eSkrBkrGLw2yULa2Cnq0ATfYkEm8VayJVafpw5_qCfVGR-LnPF0gA-v4xGcovCRzCd6dy0gZ-a3SGsnbVOekYGONjqmt_AzHoNz1rDi5-UzkctIHbPShV2ypHwqMlb5VOShEKlD0WKeFRvc5MdI4Icecvfn6JSG6JVZtpPEOi7fT149y5zW2H4zLvvws2g-U8r_VIMXn_VxaesldZjrzLkcUF5ULDipYg_PL1DbSlEm8WP53bT3DW-oiPFI6NInXc56dlGy-RwyTfqXGTvUkCFKDO3MlPNzNSur6slMIkgTjvuylCZA75WbTtnty4oG9t_WKe1ykOJQNwuG0eHjvO5Vn2gpN5sf47Axz2FUVgdM0IuqjJSw_9qG2Z0nnaLBNzsGYJm8S21MmnXaGE876CiXxkmYrk3-tI-jzaEStBgmF55V4wdXN3KM4jrtiVRpFrRS7ZDwkAYpGrnIA7Bp6e1EGTTjLUlqOM9zuOvK8PHCXMp__t-Feio80FqldDCFy1rS1oHpYjbQFqcMaTNpIFAQEHI-ULZvIhx5RpYLABjeOJRYMs8CDhnAZ7l1iale8YJW9_lI5wY0d29gKWORw7ejmPhrD2-OIF8bydmp1exu-ELkyqJoUJE30NAbo4QWiZLaNe66OygbA';
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
    'tags'                             => 1,         // 支付-第三方费
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
        $postArr['description'] = '澳亚用户'.$one->account.'在'.date('Y-m-d H:i:s',strtotime($one->created_at)).'提现';
        $postArr['transactions[0][source_name]'] = '人民币';        // Asset account
        $postArr['transactions[0][destination_name]'] = '澳亚';    // Expense account
        $postArr['date'] = date('Y-m-d',strtotime($one->created_at));
        $postArr['payment_date'] = date('Y-m-d',strtotime($one->updated_at));
        $postArr['transactions[0][amount]'] = $one->amount;
    }
    else if ($argv[1] == 'deposit')
    {
        $postArr['type'] = 'deposit';
        $postArr['description'] = '澳亚用户'.$one->user.'在'.date('Y-m-d H:i:s',strtotime($one->created_at)).'充值';
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

