<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Models\Channel;
use Weiming\Models\PaymentChannel;

class ChannelController extends BaseController
{

    public function addChannel(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => ''];

        $postDatas = $request->getParsedBody();

        if (empty($postDatas['name']) || empty($postDatas['tag'])) {

            $result['msg'] = '支付平台和支付方式不能为空';

        } else {
            $res = Channel::firstOrCreate(['tag' => $postDatas['tag']],
                [
                    'name'     => $postDatas['name'],
                    'tag'      => $postDatas['tag'],
                    'position' => $postDatas['position'],
                    'status'   => $postDatas['status'],
                    'sequence' => $postDatas['sequence'],
                ]);

            if ($res->wasRecentlyCreated) {
                $result['status'] = 0;
                $result['msg']    = '创建成功';
            }
            else {
                $result['msg'] = '支付方式重复，请重新输入';
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function updateChannel(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $postDatas = $request->getParsedBody();

        if (empty($postDatas['name']) || empty($postDatas['tag'])) {

            $result['msg'] = '支付方式和通道标识不能为空';

        } else {

            $oChannel            = Channel::find($args['id']);
            $oChannel->name      = $postDatas['name'];
            $oChannel->tag       = $postDatas['tag'];
            $oChannel->position  = $postDatas['position'];
            $oChannel->status    = $postDatas['status'];
            $oChannel->sequence  = $postDatas['sequence'];
            $res = $oChannel->save();

            if ($res) {

                $result['status'] = 0;
                $result['msg']    = '更新成功';
            }
            else
            {
                $result['msg'] = '更新失败';
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }


    public function delChannel(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $res = Channel::destroy($args['id']);

        if ($res) {

            $result['status'] = 0;
            $result['msg']    = '删除成功';

        } else {

            $result['msg'] = '删除失败';

        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @api {get} /admin/getChannels 支付方式
     * @apiGroup Vendor
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function getChannels(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        $page    = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 100;
        $name    = isset($getDatas['name']) ? $getDatas['name'] : '';
        $position= isset($getDatas['position'])? intval($getDatas['position']) : '';
        $status  = isset($getDatas['status'])? $getDatas['status'] : -1;

        Paginator::currentPathResolver(function () {return "/admin/getChannels";});
        Paginator::currentPageResolver(function () use ($page) {return $page;});

        $result = Channel::whereRaw('1=1');
        if ($name)
        {
            $result = $result->where('name', $name);
        }
        if ($position)
        {
            $result = $result->where('position', $position);
        }
        if ($status==='1' || $status==='0')
        {
            $result = $result->where('status', $status);
        }
        $result = $result->orderBy('sequence', 'asc')->orderBy('updated_at', 'desc')->orderBy('status', 'desc')->paginate($perPage);

        $response->getBody()->write($result->toJson());

        return $response;
    }
}
