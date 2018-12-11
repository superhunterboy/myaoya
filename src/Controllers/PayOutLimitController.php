<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\Level;
use \Weiming\Models\PayOutLimit;

class PayOutLimitController extends BaseController
{
    /**
     * @api {get} /admin/getPayOutLimit 获取会员出款层级限制
     * @apiName GetPayOutLimit
     * @apiGroup PayOutLimit
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       {
     *           "id": 2,
     *           "level_ids": [
     *               "11867",
     *               "12669"
     *           ],
     *           "count": 0,
     *           "created_at": "2017-12-27 11:27:24",
     *           "updated_at": "2017-12-27 11:27:26",
     *           "level_names": {
     *               "11867": "单笔100以下",
     *               "12669": "待观察区域"
     *           }
     *       },
     *       {
     *           "id": 1,
     *           "level_ids": [
     *               "7489",
     *               "7490"
     *           ],
     *           "count": 3,
     *           "created_at": "2017-12-27 11:26:57",
     *           "updated_at": "2017-12-27 11:26:59",
     *           "level_names": {
     *               "7489": "未分層",
     *               "7490": "★金卡VIP★"
     *           }
     *       }
     *   ]
     */
    public function getPayOutLimit(Request $request, Response $response, $args)
    {
        $payOutLimits = PayOutLimit::orderBy('id', 'desc')->get()->toArray();
        if ($payOutLimits) {
            $Levels = $this->getMemberLevels();
            foreach ($payOutLimits as $key => $payOutLimit) {
                $levelIds                        = explode(',', $payOutLimit['level_ids']);
                $payOutLimits[$key]['level_ids'] = $levelIds;
                foreach ($levelIds as $levelId) {
                    $payOutLimits[$key]['level_names'][$levelId] = $Levels[$levelId];
                }
            }
        }
        return $response->withJson(['status' => 0, 'msg' => '', 'data' => $payOutLimits]);
    }

    /**
     * @api {get} /admin/getAvailableMemberLevels?id=:id 获取会员出款层级
     * @apiName GetAvailableMemberLevels
     * @apiGroup PayOutLimit
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款层级限制id(传id主要用于修改的情况，排除本身已经在用的层级)
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       {
     *           "id": 7489,
     *           "name": "未分層",
     *           "status": 1 // 1为不可用，0为可用
     *       },
     *       {
     *           "id": 7490,
     *           "name": "★金卡VIP★",
     *           "status": 1
     *       },
     *       {
     *           "id": 7491,
     *           "name": "★红宝VIP★",
     *           "status": 0
     *       }
     *   ]
     */
    public function getAvailableMemberLevels(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $id       = $getDatas['id'] ?? 0;
        $levels   = Level::select(['id', 'name', 'status'])->get()->toArray();
        if ($levels) {
            $usingLevels = $this->getUnavailableMemberLevels($id);
            foreach ($levels as $key => $level) {
                $status = 0;
                if (in_array($level['id'], $usingLevels)) {
                    $status = 1;
                }
                $levels[$key]['status'] = $status;
            }
        }
        return $response->withJson($levels);
    }

    /**
     * @api {post} /admin/addPayOutLimit 添加出款层级限制
     * @apiName AddPayOutLimit
     * @apiGroup PayOutLimit
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} count 每天次数限制
     * @apiParam {String} levelIds 层级id，格式：1,2,3,4,5
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功",
     *       "data": []
     *   }
     */
    public function addPayOutLimit(Request $request, Response $response, $args)
    {
        $result      = ['status' => 1, 'msg' => '添加失败', 'data' => []];
        $postDatas   = $request->getParsedBody();
        $levelIds    = trim($postDatas['levelIds'], ',');
        $count       = $postDatas['count'] ?? 0;
        $levelIdsNum = count(explode(',', $levelIds));
        if ($levelIdsNum > 1 || ($levelIdsNum == 1 && $levelIds > 0)) {
            $payOutLimit = PayOutLimit::create([
                'level_ids' => $levelIds,
                'count'     => $count,
            ]);
            if ($payOutLimit) {
                $result['status'] = 0;
                $result['msg']    = '添加成功';
            }
        } else {
            $result['msg'] = '层级不能为空';
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/editPayOutLimit/:id 修改出款层级限制
     * @apiName EditPayOutLimit
     * @apiGroup PayOutLimit
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款层级限制id
     * @apiParam {Number} count 每天次数限制
     * @apiParam {String} levelIds 层级id，格式：1,2,3,4,5
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function editPayOutLimit(Request $request, Response $response, $args)
    {
        $result      = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $postDatas   = $request->getParsedBody();
        $levelIds    = trim($postDatas['levelIds'], ',');
        $count       = $postDatas['count'] ?? 0;
        $id          = $args['id'];
        $levelIdsNum = count(explode(',', $levelIds));
        if ($id > 0 && ($levelIdsNum > 1 || ($levelIdsNum == 1 && $levelIds > 0))) {
            if (PayOutLimit::where('id', '=', $id)->update([
                'level_ids' => $levelIds,
                'count'     => $count,
            ])) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
            }
        } else {
            $result['msg'] = 'ID或者层级不能为空';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deletePayOutLimit/:id 删除出款层级限制
     * @apiName DeletePayOutLimit
     * @apiGroup PayOutLimit
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款层级限制id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "删除成功",
     *       "data": []
     *   }
     */
    public function deletePayOutLimit(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $id     = $args['id'];
        if ($id > 0) {
            if (PayOutLimit::where('id', '=', $id)->delete()) {
                $result['status'] = 0;
                $result['msg']    = '删除成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 获取层级id=>name关联数组
     * @return Array
     */
    private function getMemberLevels()
    {
        $result = [];
        $levels = Level::all()->toArray();
        if ($levels) {
            foreach ($levels as $level) {
                $result[$level['id']] = $level['name'];
            }
        }
        return $result;
    }

    /**
     * 获取已经在用的层级ids
     * @return Array
     */
    private function getUnavailableMemberLevels($id)
    {
        $resArr       = [];
        $payOutLimits = [];
        if ($id > 0) {
            $payOutLimits = PayOutLimit::where('id', '!=', $id)->get()->toArray();
        } else {
            $payOutLimits = PayOutLimit::all()->toArray();
        }
        if ($payOutLimits) {
            $tmpStr = '';
            foreach ($payOutLimits as $payOutLimit) {
                $tmpStr .= ',' . $payOutLimit['level_ids'];
            }
            $resArr = explode(',', $tmpStr);
        }
        return $resArr;
    }
}
