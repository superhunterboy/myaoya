<?php

namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Jobs\AutoJudgment;
use Weiming\Libs\Utils;

class AutoJudgmentController extends BaseController
{
    //状态文件路径
    private $path = __DIR__ . '/../../config/autoJudgment.txt';

    /**
     * @api {get} /admin/switchJudgment 自动判断第三方是否可用
     * @apiName SwitchJudgment
     * @apiGroup Judgment
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} state 自动判断开关 0:关 1:开 不传参数获取状态
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "成功!",
     *       "data": "1"
     *   }
     */
    public function switchJudgment(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '状态更改失败失败', 'data' => []];
        $getDatas = $request->getQueryParams();
        $state = $getDatas['state'] ?? '';
        if ($state === ''){
            $bool = is_file($this->path);
            if ($bool) {
                $switch = file_get_contents($this->path);
            }else{
                $switch = '0';
            }
            return $response->withJson(['status'=>0, 'msg'=>'获取状态成功', 'data'=>$switch]);
        }else{
            if ($state != '1') {
                $state = '0';
            }
            $size = file_put_contents($this->path, $state);//var_dump($size);exit;
            if ($size === false || $size < 1) {
                return $response->withJson($result);
            }

            $result['status'] = 0;
            $result['msg'] = '成功!';
            $result['data'] = $state;
        }

        return $response->withJson($result);
    }

    //用于定时任务调用
    public function start(Request $request, Response $response, $args)
    {
        set_time_limit(240);
        file_put_contents($this->path, '1');
        //$postDatas = $request->getParsedBody();
        //$sign = $postDatas['sign'] ?? '';
        //$mySign = file_get_contents(__DIR__ . '/../../config/sign.txt');

        //if ($sign == $mySign) {
            file_put_contents(__DIR__ . '/../../logs/autoJudgment-' . date('Y-m').'.txt', date('Y-m-d H:i:s') . "进行一次支付平台匹配" . PHP_EOL, FILE_APPEND);
            return AutoJudgment::start($this->path);
        //}

    }
}