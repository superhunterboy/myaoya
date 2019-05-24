<?php
namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Models\Affiche;
use Weiming\Models\Paystatus;

class AfficheController extends BaseController
{
    /**
     * @api {post} /admin/addAffiche 增加公告
     * @apiName AddAffiche
     * @apiGroup Affiche
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {String} title 公告标题
     * @apiParam {String} content 公告内容
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功!",
     *       "data": {
     *           "title": "like you",
     *           "content": "what are you donig?",
     *           "updated_at": "2018-05-02 09:48:49",
     *           "created_at": "2018-05-02 09:48:49",
     *           "id": 2
     *       }
     *   }
     */
    public function addAffiche(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '添加失败!', 'data' => []];
        $getDatas = $request->getParsedBody();
        $title    = $getDatas['title'] ?? '';
        $content  = $getDatas['content'] ?? '';

        $data = Affiche::create(['title' => $title, 'content' => $content]);
        if ($data) {
            $result = ['status' => 0, 'msg' => '添加成功!', 'data' => $data];
        }

        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateAffiche 更新公告
     * @apiName UpdateAffiche
     * @apiGroup Affiche
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * $apiParam {Number} id 公告id
     * @apiParam {String} title 公告标题
     * @apiParam {String} content 公告内容
     * @apiParam {Number} status 0不发布 1发布
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功!",
     *       "data": 1
     *   }
     */
    public function updateAffiche(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '更新失败!', 'data' => []];
        $getDatas = $request->getParsedBody();
        $id       = $getDatas['id'] ?? 0;
        $status   = $getDatas['status'] ?? 0;
        $title    = $getDatas['title'] ?? '';
        $content  = $getDatas['content'] ?? '';

        $updateData = [];
        if (!empty($title)) {
            $updateData['title'] = $title;
        }
        if (!empty($status)) {
            $updateData['status'] = $status;
        }
        if (!empty($content)) {
            $updateData['content'] = $content;
        }

        if (!empty($id)) {
            $data = Affiche::where('id', $id)->update($updateData);
            if ($data) {
                $result = ['status' => 0, 'msg' => '更新成功!', 'data' => $data];
            }
        }

        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deleteAffiche/id 删除公告
     * @apiName DeleteAffiche
     * @apiGroup Affiche
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 公告标题
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "删除成功!",
     *       "data": 1
     *   }
     */
    public function deleteAffiche(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败!', 'data' => []];
        $id     = $args['id'] ?? 0;

        if (!empty($id)) {
            $data = Affiche::where('id', $id)->delete();
            if ($data) {
                $result = ['status' => 0, 'msg' => '删除成功!', 'data' => $data];
            }
        }

        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/affiches 获取所有公告
     * @apiName Affiches
     * @apiGroup Affiche
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "total": 1,
     *       "data": [
     *           {
     *               "id": 2,
     *               "title": "like you",
     *               "content": "what are you donig?",
     *               "status": 1,
     *               "created_at": "2018-05-02 09:48:49",
     *               "updated_at": "2018-05-02 09:52:31"
     *           }
     *       ]
     *   }
     */
    public function affiches(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;
        $offset   = ($page - 1) * $perPage;

        $affiche          = Affiche::orderBy('status', 'desc')->orderBy('updated_at', 'desc');
        $result['status'] = 0;
        $result['total']  = $affiche->count();
        $result['data']   = $affiche->skip($offset)->limit($perPage)->get();

        return $response->withJson($result);
    }

    /**
     * @api {get} /affiche 获取发布公告
     * @apiName Affiche
     * @apiGroup Affiche
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status":0,
     *       "msg":"okey",
     *       "data":[
     *           {
     *               "title":"like you",
     *               "content":"what are you donig?"
     *           }
     *       ]
     *   }
     */
    public function affiche(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '没有发布公告!', 'data' => []];
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;
        $offset   = ($page - 1) * $perPage;

        $data = Affiche::where('status', 1)->get(['title', 'content']);
        if (count($data) > 0) {
            $result = ['status' => 0, 'msg' => 'okey', 'data' => $data];
        }

        return $response->withJson($result);
    }

    /*
     * 支付开关列表
     */
    public function paymenu(Request $request, Response $response, $args){
        $result   = ['status' => 1, 'msg' => '没有发布公告!', 'data' => []];
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;
        $offset   = ($page - 1) * $perPage;

        $data = Paystatus::select()->get(['title', 'content']);
        if (count($data) > 0) {
            $result = ['status' => 0, 'msg' => 'okey', 'data' => $data];
        }

        return $response->withJson($result);
    }

    /*
     * 更新状态
     */
    public function paymenustatus(Request $request, Response $response, $args){
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $id          = $args['id'] ?? '';
        $status      = $postDatas['status'] ?? '';
        $data['status']=$status;
        $state = Paystatus::where('id', '=', $id)->update(['status' => $status]);
        if($state){
            $result    = ['status' => 0, 'msg' => '更新成功', 'data' => []];
        }
        return $response->withJson($result);
    }
}
