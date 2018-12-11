<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\Picture;

class PictureController extends BaseController
{
    /**
     * @api {post} /admin/addPicture 添加图片
     * @apiName AddPicture
     * @apiGroup Picture
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *       "picture": "",
     *       "enabled": 1,
     *       "remark": ""
     *   }
     *
     * @apiSuccess {Number} status 0 为成功，1 为失败
     * @apiSuccess {String} msg  提示消息
     * @apiSuccess {Json} data  数据
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *     "status": 0,
     *     "msg": "添加成功",
     *     "data": []
     *   }
     */
    public function addPicture(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];
        // $postDatas = file_get_contents('php://input');
        // $postDatas = json_decode($postDatas, true);
        $postDatas = $request->getParsedBody();
        if (Picture::create($postDatas)) {
            $result['status'] = 0;
            $result['msg']    = '添加成功';
        } else {
            $result['msg'] = '添加失败';
        }
        return $response->withJson($result);
    }

    /**
     * @api {delete} /admin/deletePicture/:id 删除图片
     * @apiName DeletePicture
     * @apiGroup Picture
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccess {Number} status 0 为成功，1 为失败
     * @apiSuccess {String} msg  提示消息
     * @apiSuccess {Json} data  数据
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *     "status": 0,
     *     "msg": "删除成功",
     *     "data": []
     *   }
     */
    public function deletePicture(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];
        $id     = $args['id'];
        if (Picture::find($id)->delete()) {
            $result['status'] = 0;
            $result['msg']    = '删除成功';
        } else {
            $result['msg'] = '删除失败';
        }
        return $response->withJson($result);
    }

    /**
     * @api {put} /admin/updatePicture/:id 更新图片
     * @apiName UpdatePicture
     * @apiGroup Picture
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *       "picture": "",
     *       "enabled": 1,
     *       "remark": ""
     *   }
     *
     * @apiSuccess {Number} status 0 为成功，1 为失败
     * @apiSuccess {String} msg  提示消息
     * @apiSuccess {Json} data  数据
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *     "status": 0,
     *     "msg": "更新成功",
     *     "data": []
     *   }
     */
    public function updatePicture(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];
        $id     = $args['id'];
        // $postDatas = file_get_contents('php://input');
        // $postDatas = json_decode($postDatas, true);
        $postDatas = $request->getParsedBody();
        $picture   = $postDatas['picture'];
        $enabled   = isset($postDatas['enabled']) ? $postDatas['enabled'] : 1;
        $remark    = isset($postDatas['remark']) ? $postDatas['remark'] : '';
        if (Picture::find($id)->update(['picture' => $picture, 'enabled' => $enabled, 'remark' => $remark])) {
            $result['status'] = 0;
            $result['msg']    = '更新成功';
        } else {
            $result['msg'] = '更新失败';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/getPictures 图片列表
     * @apiName GetPictures
     * @apiGroup Picture
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为10
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *     "total": 2,
     *     "per_page": 1,
     *     "current_page": 1,
     *     "last_page": 2,
     *     "next_page_url": "/admin/getPictures?page=2",
     *     "prev_page_url": null,
     *     "from": 1,
     *     "to": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "picture": "/uploadfiles/1.png",
     *         "enabled": 0,
     *         "remark": "hello",
     *         "created_at": "2017-04-09 18:09:34",
     *         "updated_at": "2017-04-09 18:17:39"
     *       }
     *     ]
     *   }
     */
    public function getPictures(Request $request, Response $response, $args)
    {
        $result   = [];
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 100;

        Paginator::currentPathResolver(function () {return "/admin/getPictures";});
        Paginator::currentPageResolver(function () use ($page) {return $page;});

        $currentUserType = $this->jwt->userInfo->type;

        // 管理员看全部业务平台
        if ($currentUserType == 1) {

            $result = Picture::paginate($perPage);

        } else {

            $companiesArr = explode(',', $this->jwt->userInfo->company_ids);

            $result = Picture::whereIn('company_id', $companiesArr)->paginate($perPage);

        }

        return $response->withJson($result);
    }

    /**
     * @api {get} /getEnabledPicture/:companyId 获取启用的图片
     * @apiName GetEnabledPicture
     * @apiGroup Picture
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *     "status": 1,
     *     "msg": "暂无启用的图片",
     *     "data": []
     *   }
     */
    public function getEnabledPicture(Request $request, Response $response, $args)
    {
        $result     = ['status' => 1, 'msg' => '', 'data' => []];
        $companyId  = $args['companyId'];
        $picture    = Picture::where('company_id', '=', $companyId)->where('enabled', '=', 1);
        if ($picture->count()) {
            $result['status'] = 0;
            $result['data']   = $picture->get()->toArray();
        } else {
            $result['msg'] = '暂无启用的图片';
        }
        return $response->withJson($result);
    }
}
