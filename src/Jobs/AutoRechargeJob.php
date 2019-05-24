<?php

namespace Weiming\Jobs;

use GuzzleHttp\Client;
use Resque_Event;
use Weiming\Jobs\BaseJob;
use Weiming\Models\Pay;
use Weiming\Models\Lock;
use Exception;

/**
 * 自动充值 Job
 */
class AutoRechargeJob extends BaseJob
{
    public function perform()
    {
        /**
         * 提交数据格式
         * Array
         * (
         *     [orderNo] => 20170808101237
         *     [account] => silen.36f@gmail.com
         *     [fee] => 1000
         *     [rechargeTime] => 2017-08-08 10:12:37
         *     [sign] => b3592d13ca0975e9fe1d4280b65e414d
         * )
         */
        $rechargeUrl             = $this->args['rechargeUrl'];
        $requestParams           = $this->args['requestParams'];
        $requestParams['act']    = 'useRecharge';
        $requestParams['action']    = 'pay';
        $requestParams['remark'] = '第三方支付';

        // 未知错误
        $rechargeStatus = 8;
        $rechargeMsg    = '未知错误';
        file_put_contents(__DIR__ . '/../../logs/AutoRechargeJob' . date('Ymd') . '.txt', var_export($requestParams,true) . "\n", FILE_APPEND | LOCK_EX);
        // 当前处理的订单
        $pay = Pay::where('order_no', '=', $requestParams['orderNo'])->first();
        if ($pay) {
            try{
                $res=Lock::insert(['order_no' => $requestParams['orderNo'], 'created_at'=>date('Y-m-d H:i:s')]);
            }
            catch(Exception $e)
            {
                file_put_contents(__DIR__ . '/../../logs/lock' . date('Ymd') . '.txt', var_export($requestParams,true) . "\n", FILE_APPEND | LOCK_EX);
                return true;
            }
            $client = new Client([
                //'http_errors' => false,
                'timeout' => 30
            ]);


            $res = $client->request('POST', $rechargeUrl, ['form_params' => $requestParams]);
            /*
            $return = array();
            $resDatalog = $res->getBody();
            $resDatalog = json_decode($resDatalog, true);
            $return['orderNo']  =$requestParams['orderNo'];
            $return['data']     =$resDatalog;
            file_put_contents(__DIR__ . '/../../logs/lock_return' . date('Ymd') . '.txt', var_export($return,true) .$http_code. "\n", FILE_APPEND | LOCK_EX);
             */
            if ($res->getStatusCode() == '200') {
                /**
                 * 充值接口返回数据格式
                 * {'ret': 0, 'text': '用户不存在', 'order_no': '20170808101237'}
                 * {'ret': 2, 'text': '网络错误', 'order_no': '20170808101237'}
                 * {'ret': 1, 'text': '充值成功', 'order_no': '20170808101237'}
                 * {'ret': 0, 'text': '权限不足', 'order_no': '20170808101237'}
                 * {'ret': 0, 'text': '充值金额错误：0', 'order_no': '20170808101237'}
                 */
                $resData = $res->getBody();
                $resData = json_decode($resData, true);

                $ret  = $resData['ret'];
                $text = $resData['text'];
                // $orderNo = $resData['order_no'];

                if ($ret == -1) {
                    // 签名错误
                    $rechargeStatus = 4;

                } elseif ($ret == 0) {
                    // 充值有问题，仅仅记录，手动处理
                    $rechargeStatus = 2;

                } elseif ($ret == 2) {
                    // 网络错误
                    $rechargeStatus = 3;

                } elseif ($ret == 1) {
                    // 成功
                    $rechargeStatus = 9;

                } elseif ($ret == 8) {
                    // bbin 10 秒内防止重复入款处理
                    Resque_Event::trigger('onFailure');
                }

                $rechargeMsg = $text;
            }

            $pay->recharge_status = $rechargeStatus; // 0 未处理，1 已加入队列，2 充值有问题需手动处理，3 网络错误，4 签名错误，8 未知错误，9 充值成功
            $pay->recharge_count  = $pay->recharge_count + 1; // 正常只充值 1 次
            $pay->recharge_msg    = $rechargeMsg;
            $pay->rk_user_id      = 0; // 自动入款用户id为 0，用户名 system
            $pay->rk_user         = 'system';
            $pay->rk_status       = $ret == 1 ? 1 : 0;
            $pay->save();
        }
    }
}
