<?php
/**
 * Created by PhpStorm.
 * User: jae
 * Date: 2019/3/4
 * Time: 15:50
 */
namespace Weiming\Controllers;
use GuzzleHttp\Client;
use Illuminate\Pagination\Paginator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Utils;
use Weiming\Models\Pay;


class SprdercallbackController extends BaseController
{

    public function updatestatus(Request $request, Response $response, $args){
        //爬虫服务器请求参数
        $postDatas = $request->getParsedBody();
        $postDatas=json_decode($postDatas['jsonData'],true);
        $ret=$postDatas['ret'];
        $text=$postDatas['text'];
        $orderNo=$postDatas['orderNo'];
        $act=$postDatas['action'];
        if($act == "pay"){  //在线支付
            $pay = Pay::where('order_no', '=', $orderNo)->first();
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
            }

            $pay->recharge_status = $rechargeStatus; // 0 未处理，1 已加入队列，2 充值有问题需手动处理，3 网络错误，4 签名错误，8 未知错误，9 充值成功
            $pay->recharge_count  = $pay->recharge_count + 1; // 正常只充值 1 次
            $pay->recharge_msg    = $text;
            $pay->rk_user_id      = 0; // 自动入款用户id为 0，用户名 system
            $pay->rk_user         = 'system';
            $pay->rk_status       = $ret == 1 ? 1 : 0;
            $result=$pay->save();
            $data=array();
            if($result){
                $data['msg']="订单状态已更新:".$orderNo;
                file_put_contents(__DIR__ . '/../../logs/lock_return_sprdercallback' . date('Ymd') . '.txt', print_r($data,true).print_r($postDatas,true). "\n", FILE_APPEND | LOCK_EX);
                return json_encode($data);
            }
        }elseif($act == "qrcode"){     //更新个人二维码支付状态

        }




    }
}