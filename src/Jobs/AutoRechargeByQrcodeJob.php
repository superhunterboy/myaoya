<?php

namespace Weiming\Jobs;

use GuzzleHttp\Client;
use Resque_Event;
use Weiming\Jobs\BaseJob;
use Weiming\Models\PayQrcode;

/**
 * 微信扫码自动充值 Job
 */
class AutoRechargeByQrcodeJob extends BaseJob
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
        $requestParams['remark'] = '智能微信收款码';

        // 未知错误
        $rechargeStatus = 8;
        $rechargeMsg    = '未知错误';

        // 当前处理的订单
        $payQrcode = PayQrcode::where('code', '=', $requestParams['orderNo'])->first();
        if ($payQrcode) {
            $client = new Client([
                'http_errors' => false,
                'timeout' => 30
            ]);
            $res    = $client->request('POST', $rechargeUrl, ['form_params' => $requestParams]);
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
                file_put_contents(__DIR__ . '/../../logs/wechatGroup.log', date('Y-m-d H:i:s') . ' - ' . json_encode($requestParams) . ' - ' . $resData, FILE_APPEND | LOCK_EX);
                $resData = json_decode($resData, true);
                $ret     = $resData['ret'];
                $text    = $resData['text'];
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
            $payQrcode->result     = $rechargeMsg;
            $payQrcode->rk_user_id = 0; // 自动入款用户id为 0，用户名 system
            $payQrcode->rk_user    = 'system';
            $payQrcode->status     = $ret == 1 ? 2 : 3;
            $payQrcode->save();
        }
    }
}
