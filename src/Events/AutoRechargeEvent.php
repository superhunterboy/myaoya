<?php

namespace Weiming\Events;

use Weiming\Jobs\AutoPayOutJob;
use Weiming\Jobs\AutoRechargeByQrcodeJob;
use Weiming\Jobs\AutoRechargeJob;
use Weiming\Models\Pay;
use Weiming\Models\PayQrcode;
use Weiming\Models\Retry;

class AutoRechargeEvent
{
    public static function onFailure($exception, $job)
    {
        $obj     = $job->getInstance();
        $jobData = $job->getArguments();
        if ($obj instanceof AutoRechargeJob) {
            // 自动充值
            $orderNo = $jobData['requestParams']['orderNo'];
            // 记录重试次数
            Retry::updateOrCreate(['order_no' => $orderNo], ['order_no' => $orderNo])->increment('count');
            $pay = Pay::where('order_no', '=', $orderNo)->first();
            if ($pay) {
                $retry = Retry::where('order_no', '=', $orderNo)->first();
                if ($retry->count <= 3) {
                    // 重新添加任务
                    $token = $job->recreate();
                    if ($token) {
                        // 1 已加入队列
                        $pay->recharge_status = 1;
                        $pay->recharge_msg    = '正在自动入款';
                        $pay->queue_job_id    = $token;
                        $pay->save();
                    }
                } else {
                    // 手动入款
                    $pay->recharge_status = 2;
                    $pay->recharge_msg    = '3次入款失败请手动入款';
                    $pay->save();
                }
            }
        } elseif ($obj instanceof AutoRechargeByQrcodeJob) {
            // 自动充值
            $orderNo = $jobData['requestParams']['orderNo'];
            // 记录重试次数
            Retry::updateOrCreate(['order_no' => $orderNo], ['order_no' => $orderNo])->increment('count');
            $payQrcode = PayQrcode::where('code', '=', $orderNo)->first();
            if ($payQrcode) {
                $retry = Retry::where('order_no', '=', $orderNo)->first();
                if ($retry->count <= 3) {
                    // 重新添加任务
                    $token = $job->recreate();
                    if ($token) {
                        // 1 已加入队列
                        $payQrcode->status       = 1;
                        $payQrcode->result       = '正在自动入款';
                        $payQrcode->queue_job_id = $token;
                        $payQrcode->save();
                    }
                } else {
                    // 手动入款
                    $payQrcode->status = 3;
                    $payQrcode->result = '3次入款失败请手动入款';
                    $payQrcode->save();
                }
            }
        } elseif ($obj instanceof AutoPayOutJob) {
            // 自动出款
            echo "Pay out failure.\n";
        }
    }
}
