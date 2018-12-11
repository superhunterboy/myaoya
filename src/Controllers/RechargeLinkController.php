<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\Recharge;
use \Weiming\Models\RechargeLink;

class RechargeLinkController extends BaseController
{
    /**
     * @api {post} /admin/addLink 添加出款充值链接
     * @apiName AddLink
     * @apiGroup RechargeLink
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} platform_id 出款平台id
     * @apiParam {String} remark 链接备注
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *     "status": 0,
     *     "msg": "添加成功",
     *     "data": []
     *   }
     */
    public function addLink(Request $request, Response $response, $args)
    {
        $result     = ['status' => 1, 'msg' => '', 'data' => []];
        $postDatas  = $request->getParsedBody();
        $platformId = $postDatas['platform_id'] ?? 0;
        $remark     = $postDatas['remark'] ?? '';
        if ($platformId > 0 && !empty($remark)) {
            $postDatas['token'] = md5(uniqid('', true));
            if (RechargeLink::create($postDatas)) {
                $result['status'] = 0;
                $result['msg']    = '添加成功';
            } else {
                $result['msg'] = '添加失败';
            }
        } else {
            $result['msg'] = '参数不正确';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deleteLink/:id 删除出款充值链接
     * @apiName DeleteLink
     * @apiGroup RechargeLink
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款充值链接id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "删除成功",
     *       "data": []
     *   }
     */
    public function deleteLink(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $id     = $args['id'];
        if ($id > 0) {
            if (Recharge::where('recharge_link_id', '=', $id)->count() == 0 && RechargeLink::find($id)->delete()) {
                $result['status'] = 0;
                $result['msg']    = '删除成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/editLink/:id 修改出款充值链接
     * @apiName EditLink
     * @apiGroup RechargeLink
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款充值链接id
     *
     * @apiParam {Number} platform_id 出款平台id
     * @apiParam {String} remark 链接备注
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "修改成功",
     *       "data": []
     *   }
     */
    public function editLink(Request $request, Response $response, $args)
    {
        $result     = ['status' => 1, 'msg' => '修改失败', 'data' => []];
        $postDatas  = $request->getParsedBody();
        $platformId = $postDatas['platform_id'] ?? 0;
        $remark     = $postDatas['remark'] ?? '';
        if ($platformId > 0 && !empty($remark)) {
            if (RechargeLink::find($args['id'])->update($postDatas)) {
                $result['status'] = 0;
                $result['msg']    = '修改成功';
            }
        } else {
            $result['msg'] = '参数不正确';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/rechargeLinks?platformId=:platformId&remark=:remark&page=:page&perPage=:perPage 出款充值链接列表
     * @apiName RechargeLinks
     * @apiGroup RechargeLink
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数
     * @apiParam {Number} platformId 出款平台ID
     * @apiParam {String} remark 备注
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 1,
     *               "platform_id": 27,
     *               "token": "7b9e49b79200cb8069dff056cd6aba2b",
     *               "remark": "张三",
     *               "status": 0,
     *               "created_at": "2018-03-29 11:13:41",
     *               "updated_at": "2018-03-29 13:51:58",
     *               "platform": {
     *                   "id": 27,
     *                   "company_id": 1,
     *                   "pay_out_type": 13,
     *                   "no": "100038",
     *                   "key": "d1a288e0e302c561b4e15186e536abb4",
     *                   "callback_url": "http://157a.com/recharge/tianji/callback",
     *                   "notify_url": "http://157a.com/recharge/tianji/notify",
     *                   "start_amount_limit": "0.00",
     *                   "end_amount_limit": "0.00",
     *                   "balance": "0.00",
     *                   "enabled": 0,
     *                   "type": 0,
     *                   "created_at": "2018-03-14 09:25:49",
     *                   "updated_at": "2018-03-29 11:35:53"
     *               }
     *           }
     *       ],
     *       "first_page_url": "/admin/rechargeLinks?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "/admin/rechargeLinks?page=1",
     *       "next_page_url": null,
     *       "path": "/admin/rechargeLinks",
     *       "per_page": 20,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *   }
     */
    public function rechargeLinks(Request $request, Response $response, $args)
    {
        $getDatas   = $request->getQueryParams();
        $platformId = $getDatas['platformId'] ?? 0;
        $remark     = $getDatas['remark'] ?? '';
        $page       = $getDatas['page'] ?? 1;
        $perPage    = $getDatas['perPage'] ?? 20;
        Paginator::currentPathResolver(function () {
            return "/admin/rechargeLinks";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $rechargeLink = RechargeLink::with('platform');
        if ($platformId > 0) {
            $rechargeLink = $rechargeLink->where('platform_id', '=', $platformId);
        }
        if ($remark) {
            $rechargeLink = $rechargeLink->where('remark', 'LIKE', "%{$remark}%");
        }
        $result = $rechargeLink->where('id', '>', 0)->orderBy('id', 'DESC')->paginate($perPage);
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/updateLinkStatus/:id 修改出款充值链接状态
     * @apiName UpdateLinkStatus
     * @apiGroup RechargeLink
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 出款充值链接id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": {
     *           "id": 1,
     *           "platform_id": 27,
     *           "token": "7b9e49b79200cb8069dff056cd6aba2b",
     *           "remark": "张三",
     *           "status": 1, // 0启用、1禁用，每调用此接口，状态就会在0、1之间切换
     *           "created_at": "2018-03-29 11:13:41",
     *           "updated_at": "2018-03-29 13:38:27"
     *       }
     *   }
     */
    public function updateLinkStatus(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id     = $args['id'];
        if ($id > 0) {
            $rechargeLink = RechargeLink::find($id);
            $status       = $rechargeLink->status;
            if ($status == 0) {
                $rechargeLink->status = 1;
            } elseif ($status == 1) {
                $rechargeLink->status = 0;
            }
            if ($rechargeLink->save()) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
                $result['data']   = $rechargeLink;
            }
        }
        return $response->withJson($result);
    }
}
