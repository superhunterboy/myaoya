<?php

namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Models\Level;

class LevelController extends BaseController
{
    /**
     * @api {get} /admin/levels 获取层级信息
     * @apiName levels
     * @apiGroup Level
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "成功",
     *       "data": [
     *           {
     *               "id": 1,
     *               "name": "vip1",
     *               "status": "1", //0未占用 1已占用
     *               "created_at": "-0001-11-30 00:00:00",
     *               "updated_at": "-0001-11-30 00:00:00"
     *           }
     *       ]
     *   }
     */
    public function levels(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '获取失败!', 'data' => []];

        $levels = Level::all();
        if ($levels) {
            $result['status'] = 0;
            $result['msg']    = '成功';
            $result['data']   = $levels;
        }
        return $response->withJson($result);
    }

    public function addLevels(Request $request, Response $response, $args)
    {
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('Member Level Data.', $postDatas);
        if (isset($postDatas['jsonData']) && $postDatas['jsonData']) {
            $tmpArr = json_decode($postDatas['jsonData'], true);
            foreach ($tmpArr as $key => $val) {
                // Array
                // (
                //     [0] => 34
                //     [1] => 13428                // 层级id
                //     [2] => B◆金卡VIP            // 层级名称
                //     [3] => Array
                //         (
                //             [0] => 2001-01-01 00:00:00  // 会员加入期间
                //             [1] => 2030-01-01 00:00:00
                //         )

                //     [4] => 0                 // 存款次数
                //     [5] => 0                 // 存款总额
                //     [6] => 0                 // 最大存款额度
                //     [7] => 0                 // 提款次数
                //     [8] => 0                 // 提款总额
                //     [9] => 5                 // 会员人数
                //     [10] => B◆金卡VIP        // 备注
                //     [11] =>
                // )
                $id     = $val[1] ?? '';
                $name   = $val[2] ?? '';
                $remark = json_encode($val);
                $level  = Level::updateOrCreate(['id' => $id], [
                    'id'     => $id,
                    'name'   => $name,
                    'remark' => $remark,
                ]);
                // if ($level && $level->wasRecentlyCreated) {
                // }
            }
            $response->getBody()->write("Ok, Members level data has been submitted to the payment system.\n");
        }
        return $response;
    }
}
