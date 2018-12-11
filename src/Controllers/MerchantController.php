<?php

namespace Weiming\Controllers;

use Illuminate\Pagination\Paginator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Utils;
use Weiming\Models\Merchant;
use Weiming\Libs\Payments\PingAn;

class MerchantController extends BaseController
{
    /**
     * @api {get} /addMerchant 添加商户(自动拉取,请勿操作)
     * @apiName AddMerchant
     * @apiGroup Merchant
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} open_id 机构编号 (可选)
     * @apiParam {String} open_key 机构公钥 (可选)
     * @apiParam {String} shop_no 门店编号 (建议配置,不配置可能无法获取二维码)
     * @apiParam {Json}   key 密钥key:{"public":"...","private":"..."} (可选)
     * @apiParam {String} merchant_name 商户名称
     * @apiParam {String} signboard_name 招牌名字
     * @apiParam {String} address 所在地址
     * @apiParam {Number} type 二维码类型 1:微信 2:支付宝(默认为所有:1,2)
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功",
     *       "data": []
     *   }
     */
    public function addMerchant(Request $request, Response $response, $args)
    {
        set_time_limit(240);

        $pingAnObj = Pingan::getInstance([]);
        $merchants = $pingAnObj->signature('2')->payment();
        $merchantArr = json_decode($merchants, true);

        if(is_array($merchantArr)){
            //日志
            $this->logger->addInfo('Merchants data:', $merchantArr);
            //验签
            $boolean = $pingAnObj->verifySign($merchantArr);
            if ($boolean) {
                //解析数据
                $data = $pingAnObj->aes_decode($merchantArr['data']);                                                                                           //var_dump($data);exit;
                if ($result = json_decode($data, true)) {
                    foreach ($result['list'] as $key => $value) {
                        $merState = Merchant::where('shop_no', $value['mct_no'])->first();
                        if($merState){
                            $merState->where('shop_no', $value['mct_no'])->update([
                                'open_id'        => $value['agent_no'],
                                'merchant_name'  => $value['mct_name'],
                                'signboard_name' => $value['brand_name'],
                                'address'        => $value['address'],
                            ]);
                        }else{
                            Merchant::create([
                                'shop_no'        => $value['mct_no'],
                                'open_id'        => $value['agent_no'],
                                'merchant_name'  => $value['mct_name'],
                                'signboard_name' => $value['brand_name'],
                                'address'        => $value['address'],
                            ]);
                        }
                    }
                    if ($result['totalpage'] > 1) {
                        for ($i=1; $i < $result['totalpage']; $i++) {
                            $merchants = $pingAnObj->signature('2', $i+1)->payment();
                            $merchantArr = json_decode($merchants, true);
                            //解析数据
                            $data = $pingAnObj->aes_decode($merchantArr['data']);
                            $result = json_decode($data, true);
                            foreach ($result['list'] as $key => $value) {
                                $merState = Merchant::where('shop_no', $value['mct_no'])->first();
                                if($merState){
                                    $merState->where('shop_no', $value['mct_no'])->update([
                                        'open_id'        => $value['agent_no'],
                                        'merchant_name'  => $value['mct_name'],
                                        'signboard_name' => $value['brand_name'],
                                        'address'        => $value['address'],
                                    ]);
                                }else{
                                    Merchant::create([
                                        'shop_no'        => $value['mct_no'],
                                        'open_id'        => $value['agent_no'],
                                        'merchant_name'  => $value['mct_name'],
                                        'signboard_name' => $value['brand_name'],
                                        'address'        => $value['address'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @api {post} /admin/updateMerchant/:id 更新商户
     * @apiName UpdateMerchant
     * @apiGroup Merchant
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 商户id
     * @apiParam {String} open_id 机构编号
     * @apiParam {String} open_key 机构公钥
     * @apiParam {String} shop_no 门店编号
     * @apiParam {Json}   key 密钥key:{"open_key":"...","public_key":"...","private_key":"..."}
     * @apiParam {String} merchant_name 商户名称
     * @apiParam {String} signboard_name 招牌名字
     * @apiParam {String} address 所在地址
     * @apiParam {String} status 状态 0:未锁定 1:锁定
     * @apiParam {String} type 二维码类型 1:微信 2:支付宝
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": 1
     *   }
     */
    public function updateMerchant(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id        = $args['id']?? '';
        $postDatas = $request->getParsedBody();

        $open_id         = $postDatas['open_id'] ?? '';
        $open_key         = $postDatas['open_key'] ?? '';
        $shop_no         = $postDatas['shop_no'] ?? '';
        $merchant_name   = $postDatas['merchant_name'] ?? '';
        $signboard_name  = $postDatas['signboard_name'] ?? '';
        $address         = $postDatas['address'] ?? '';
        $status          = $postDatas['status'] ?? '';
        $key             = $postDatas['key'] ?? '';
        $type            = $postDatas['type'] ?? '';

        if (!empty($id)) {
            if ($open_id != '') { $data['open_id'] = $open_id; }
            if ($open_key != '') { $data['open_key'] = $open_key; }
            if ($shop_no != '') { $data['shop_no'] = $shop_no; }
            if ($merchant_name != '') { $data['merchant_name'] = $merchant_name;}
            if ($signboard_name != '') { $data['signboard_name'] = $signboard_name; }
            if ($address != '') { $data['address'] = $address; }
            if ($key != '') { $data['key'] = $key; }
            if ($type != '' && in_array($type, ['1', '2', '1,2'])) { $data['type'] = $type; }
            if ($status != '' && in_array($status, [0, 1])) {
                if ($status == 1) {
                    $merInfo = Merchant::where('status', '1')->whereRaw("type like '%$type%'");

                    $merInfo->whereRaw("type like '%$type%'")->update(['status' => '0']);
                }
                $data['status'] = $status;
            }

            if ($res = Merchant::where('id', '=', $id)->update($data)) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
                $result['data']   = $res;
            }
        }else{
            $result['msg'] = '缺少参数!';
        }

        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/merchants 商户列表
     * @apiName Merchants
     * @apiGroup Merchant
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} type 类型 默认所有1,2  1:微信 2:支付宝
     * @apiParam {String} keyWord 类型 搜索用关键字
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数 不提交默认为 20
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     * {
     *     "current_page": 1,
     *     "data": [
     *         {
     *           "id": 2,
     *           "open_id": "",
     *           "open_key": "",
     *           "shop_no": "20260437",
     *           "merchant_name": "Ran",
     *           "signboard_name": "haha",
     *           "address": "beijing",
     *           "status": "1",
     *           "type": "1,2",
     *           "key": "",
     *           "created_at": "2017-11-14 17:12:04",
     *           "updated_at": "2017-11-14 17:12:04"
     *       }
     *     ],
     *     "first_page_url": "/admin/merchants?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "/admin/merchants?page=1",
     *     "next_page_url": null,
     *     "path": "/admin/merchants",
     *     "per_page": 20,
     *     "prev_page_url": null,
     *     "to": 2,
     *     "total": 2
     * }
     */
    public function merchants(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $type     = $getDatas['type'] ?? '1,2';
        $keyWord  = $getDatas['keyWord'] ?? '';
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;

        Paginator::currentPathResolver(function () use ($type) {
            return "/admin/merchants?type={$type}";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $merchantObj =Merchant::whereRaw("type like '%$type%'");
        if (!empty($keyWord)) {
            $merchantObj = $merchantObj->whereRaw("`shop_no` like '%{$keyWord}%' or `merchant_name` like '%{$keyWord}%' or `signboard_name` like '%{$keyWord}%'");
        }
        $result = $merchantObj->orderBy('id', 'DESC')->paginate($perPage);

        return $response->withJson($result);
    }

    //商户回掉接口
    /*public function callback(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $postDatas = $request->getParsedBody();
        if ($getDatas && $postDatas) {
            $souseData = array_merge($getDatas, $postDatas);
        } elseif ($getDatas) {
            $souseData = $getDatas;
        } elseif ($postDatas) {
            $souseData = $postDatas;
        } else {
            return '只能获取get或者post数据!';
        }

        //日志
        $this->logger->addInfo('Pingan payment callback data:', $souseData);
    }*/

    //删除商户
    public function deleteMerchant(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $id     = $args['id'] ?? 0;
        if ($id > 0) {
            if (priQrcodePay::where('qrcode_id', '=', $id)->count() == 0) {
                if (Merchant::where('id', '=', $id)->delete()) {
                    $result['status'] = 0;
                    $result['msg']    = '删除成功';
                }
            } else {
                $result['msg'] = '该商户下有支付记录，您不能删除';
            }
        }else{
            $result['msg'] = '缺少参数!';
        }
        return $response->withJson($result);
    }

}
