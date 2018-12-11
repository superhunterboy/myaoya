<?php
include('../vendor/autoload.php');

use Weiming\Libs\AgencyPayments\Chuanhua;

$args = [
    'orderNo'  => date('YmdHis').mt_rand(100, 999),
    'tranAmt'  => 10,
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
$config     = ['parterNo'=>'1358001', 'parterKey'=>'fn6ZVgy065q7Gb99y2Iu', 'callbackUrl'=>'http://47.90.98.83/payment/singleqr/callback'];
//if ($payOutType == 4) {
    //$payOutObj = Chuanhua::getInstance($config);
    doChuanhua();
//}

//结果
/*{"code":"","count":"1","data":"","msg":"代付[BN20171126105904564]提交 成功-->状态:[待处理]","result":"success"}*/
function doChuanhua()
{
    global $config;
    global $args;

    $payOutObj = Chuanhua::getInstance($config);
    $result = $payOutObj->generateSignature($args, 'payment')->sendRequest();                         var_dump($result);

    if ($result) {                                                                                    echo '代付请求发送成功';
        $payOutStatus = $result;
        $code         = $result['code'] ?? '';
        $status       = $result['result'] ?? '01';
        $msg          = $result['msg'] ?? '';
        if (empty($code) && $status == 'success') {                                                   echo '反查询成功';
            // 处理中，10秒后反查一次代付结果
            sleep(10);
            $payOutObj1 = Chuanhua::getInstance($config);
            $payOutStatus = $payOutObj1->generateSignature([
                'orderNo' => $args['orderNo'],
            ], 'query')->sendRequest();                                                               var_dump($payOutStatus);

            $code         = $payOutStatus['code'] ?? $code;
            $status       = $payOutStatus['result'] ?? $status;
            $msg          = $payOutStatus['msg'] ?? '';
            $state        = $payOutStatus['data']['status'] ?? '';
            $orderId      = $payOutStatus['data']['businessrecordnumber'] ?? '';
            if($state == '成功' && $status == 'success'){
                $status  = '00';
            }else{
                $status  = '02';
            }
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