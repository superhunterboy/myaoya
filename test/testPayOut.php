<?php
include('../vendor/autoload.php');

use Weiming\Libs\AgencyPayments\Zesheng;

$args = [
    'orderNo'  => date('YmdHis').mt_rand(100, 999),
    'tranAmt'  => 100,
    'acctName' => '刘许为',
    'acctId'   => '6236682940002696199',
    'mobile'   => '13577890000',
    'bankName' => '建设银行',
    'province' => '广东',
    'city'     => '深圳',
];

$orderNo    = $args['orderNo'];
//$payOut     = PayOut::where('order_no', '=', $orderNo)->first();var_dump($payOut);exit;
//$platform   = Platform::whereRaw('`company_id` = ' . $payOut->company_id . ' AND `enabled` = 1')->first();
//$payOutType = 4;
$config     = ['parterNo' => '1000002004', 'parterKey' => '285cG7T7210541abA2e66aREb0b73790b'];
//if ($payOutType == 4) {
    $payOutObj = Zesheng::getInstance($config);
    doZesheng();
//}

function doZesheng()
{
    global $config;
    global $args;

    $payOutObj = Zesheng::getInstance($config);
    $result = $payOutObj->generateSignature($args, 'payment')->sendRequest();    echo json_encode($result).PHP_EOL;
    print_r($result);exit();
    if ($result) {                                                               echo '代付请求发送成功';
        $payOutStatus = $result;
        $orderId      = $result['data']['orderId'] ?? '';
        $status       = $result['code'] ?? '01';
        $msg          = $result['msg'] ?? ($result['origData']['msg'] ?? '');
        if (!empty($orderId) && $status == '01') {                               echo '反查询成功';
            // 处理中，反查一次代付结果
            $payOutObj1 = Zesheng::getInstance($config);
            $payOutStatus = $payOutObj1->generateSignature([
                'orderNo' => $args['orderNo'],
            ], 'query')->sendRequest();                                          echo json_encode($payOutStatus).PHP_EOL;
            $orderId = $payOutStatus['data']['orderId'] ?? '';
            if(isset($payOutStatus['data']['state']) && $payOutStatus['data']['state'] == '90'){
                $status  = '01';
            }else{
                $status  = $payOutStatus['data']['state'] ?? '01';
            }
            $msg     = $payOutStatus['msg'] ?? ($payOutStatus['origData']['msg'] ?? '未知');
            /*if (!empty($orderId) && $status == '00') {
                // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                $crawlerRes = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                    'act'     => 'updateStatus',
                    'id'      => $payOut->wid,
                    'status'  => 1, // 确定
                    'account' => $payOut->account,
                ]);
                if (strpos($crawlerRes, 'true') !== false) {
                    // 更新 确定 状态
                    $payOut->status = 1;
                }
            }*/
        }
        // 统一状态
        if ($status == '00') {
            $status = 1;
        } elseif ($status == '01') {
            $status = 2;
        } elseif ($status == '02') {
            $status = 3;
        } else {
            $status = 5;
        }
        echo $status;
        /*$payOut->platform_id       = $platform->id;
        $payOut->platform_type     = $platform->pay_out_type;
        $payOut->platform_order_no = $orderId;
        $payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $payOut->remark            = $msg;
        $payOut->platform_attach   = json_encode($payOutStatus);
        $payOut->save();
        if ($payOut->save()) {
            // 备注
            $crawlerRes = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                'act'     => 'updateRemark',
                'id'      => $payOut->wid,
                'content' => $msg,
            ]);
        }*/
    }
}



