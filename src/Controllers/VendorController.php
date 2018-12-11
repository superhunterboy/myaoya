<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Vendor;

class VendorController extends BaseController
{
    /**
     * @api {post} /admin/vendors 添加支付平台
     * @apiName Vendors
     * @apiGroup Vendor
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} company_id 商户平台id
     * @apiParam {Number} pay_type 支付平台序号
     * @apiParam {String} no 商户号
     * @apiParam {String} key 商户key
     * @apiParam {String} callback_url 异步回掉
     * @apiParam {String} notify_url 同步通知
     * @apiParam {Number} wechat 是否支持微信
     * @apiParam {Number} wap_wechat 是否支持微信wap
     * @apiParam {Number} alipay 是否支持支付宝
     * @apiParam {Number} wap_alipay 是否支持支付宝wap
     * @apiParam {Number} netpay 是否支持银行
     * @apiParam {Number} qq 是否支持QQ
     * @apiParam {Number} wap_qq 是否支持QQwap
     * @apiParam {Number} jd 是否支持京东
     * @apiParam {Number} wap_jd 是否支持京东wap
     * @apiParam {Number} baidu 是否支持百度
     * @apiParam {Number} wap_baidu 是否支持百度wap
     * @apiParam {Number} union 是否支持银联
     * @apiParam {Number} wap_union 是否支持银联wap
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function addVendor(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $postDatas = $request->getParsedBody();

        $company_id         = $postDatas['company_id'];
        $pay_type           = $postDatas['pay_type'];
        $no                 = $postDatas['no'];
        $key                = $postDatas['key'];
        $callback_url       = $postDatas['callback_url'];
        $notify_url         = $postDatas['notify_url'];
        $wechat             = $postDatas['wechat'] ?? 0;
        $wap_wechat         = $postDatas['wap_wechat'] ?? 0;
        $alipay             = $postDatas['alipay'] ?? 0;
        $wap_alipay         = $postDatas['wap_alipay'] ?? 0;
        $netpay             = $postDatas['netpay'] ?? 0;
        $qq                 = $postDatas['qq'] ?? 0;
        $wap_qq             = $postDatas['wap_qq'] ?? 0;
        $jd                 = $postDatas['jd'] ?? 0;
        $wap_jd             = $postDatas['wap_jd'] ?? 0;
        $baidu              = $postDatas['baidu'] ?? 0;
        $wap_baidu          = $postDatas['wap_baidu'] ?? 0;
        $union              = $postDatas['union'] ?? 0;
        $wap_union          = $postDatas['wap_union'] ?? 0;

        if (empty($company_id) || empty($pay_type) || empty($no) || empty($key)) {

            $result['msg'] = '业务平台或者支付平台名称或者支付平台商户编号、key不能为空';

        } else {

            // 商户编号唯一验证
            if (Vendor::where('no', '=', $no)->count() == 0) {

                $res = Vendor::create($postDatas);

                if ($res) {

                    $result['status'] = 0;
                    $result['msg']    = '创建支付平台成功';
                    $result['data']   = $res->toArray();
                }

            } else {

                $result['msg'] = '支付平台商户编号重复，请重新输入';
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @api {put} /admin/vendors/:id 更新支付平台
     * @apiName UpdateVendor
     * @apiGroup Vendor
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} company_id 商户平台id
     * @apiParam {Number} pay_type 支付平台序号
     * @apiParam {String} no 商户号
     * @apiParam {String} key 商户key
     * @apiParam {String} callback_url 异步回掉
     * @apiParam {String} notify_url 同步通知
     * @apiParam {Number} wechat 是否支持微信
     * @apiParam {Number} wap_wechat 是否支持微信wap
     * @apiParam {Number} alipay 是否支持支付宝
     * @apiParam {Number} wap_alipay 是否支持支付宝wap
     * @apiParam {Number} netpay 是否支持银行
     * @apiParam {Number} qq 是否支持QQ
     * @apiParam {Number} wap_qq 是否支持QQwap
     * @apiParam {Number} jd 是否支持京东
     * @apiParam {Number} wap_jd 是否支持京东wap
     * @apiParam {Number} baidu 是否支持百度
     * @apiParam {Number} wap_baidu 是否支持百度wap
     * @apiParam {Number} union 是否支持银联
     * @apiParam {Number} wap_union 是否支持银联wap
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function updateVendor(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $putDatas = $request->getParsedBody();

        $company_id         = $putDatas['company_id'];
        $pay_type           = $putDatas['pay_type'];
        $no                 = $putDatas['no'];
        $key                = $putDatas['key'];
        $callback_url       = $putDatas['callback_url'];
        $notify_url         = $putDatas['notify_url'];
        $wechat             = $putDatas['wechat'] ?? 0;
        $wap_wechat         = $putDatas['wap_wechat'] ?? 0;
        $alipay             = $putDatas['alipay'] ?? 0;
        $wap_alipay         = $putDatas['wap_alipay'] ?? 0;
        $netpay             = $putDatas['netpay'] ?? 0;
        $qq                 = $putDatas['qq'] ?? 0;
        $wap_qq             = $putDatas['wap_qq'] ?? 0;
        $jd                 = $putDatas['jd'] ?? 0;
        $wap_jd             = $putDatas['wap_jd'] ?? 0;
        $baidu              = $putDatas['baidu'] ?? 0;
        $wap_baidu          = $putDatas['wap_baidu'] ?? 0;
        $union              = $putDatas['union'] ?? 0;
        $wap_union          = $putDatas['wap_union'] ?? 0;

        if (empty($company_id) || empty($pay_type) || empty($no) || empty($key)) {

            $result['msg'] = '业务平台或者支付平台名称或者支付平台商户编号、key不能为空';

        } else {

            // 商户编号唯一验证
            if (Vendor::where('no', '=', $no)->where('id', '!=', $args['id'])->count() == 0) {

                $vendor                     = Vendor::find($args['id']);
                $vendor->company_id         = $company_id;
                $vendor->pay_type           = $pay_type;
                $vendor->no                 = $no;
                $vendor->key                = $key;
                $vendor->callback_url       = $callback_url;
                $vendor->notify_url         = $notify_url;
                $vendor->wechat             = $wechat;
                $vendor->wap_wechat         = $wap_wechat;
                $vendor->alipay             = $alipay;
                $vendor->wap_alipay         = $wap_alipay;
                $vendor->netpay             = $netpay;
                $vendor->qq                 = $qq;
                $vendor->wap_qq             = $wap_qq;
                $vendor->jd                 = $jd;
                $vendor->wap_jd             = $wap_jd;
                $vendor->baidu              = $baidu;
                $vendor->wap_baidu          = $wap_baidu;
                $vendor->union              = $union;
                $vendor->wap_union          = $wap_union;
                // $vendor->error_count = $putDatas['error_count'];
                $res = $vendor->save();

                if ($res) {

                    $result['status'] = 0;
                    $result['msg']    = '支付平台更新成功';
                }

            } else {

                $result['msg'] = '支付平台商户编号重复，请重新输入';
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @api {delete} /admin/vendors/:id 删除支付平台
     * @apiName DeleteVendor
     * @apiGroup Vendor
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function deleteVendor(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $ids = $request->getAttribute('id');

        if ($ids) {

            $ids = Utils::getIds($ids);

            $ids = implode("', '", $ids);

            // 判断业务平台是否在使用当前被删除的支付平台，有则不删除
            $notDelVendors = Company::whereRaw("`wechat_vendor_id` IN ('$ids') OR `alipay_vendor_id` IN ('$ids') OR `netbank_vendor_id` IN ('$ids') OR `qq_vendor_id` IN ('$ids') OR `jd_vendor_id` IN ('$ids') OR `baidu_vendor_id` IN ('$ids') OR `union_vendor_id` IN ('$ids') OR `wap_wechat_vendor_id` IN ('$ids') OR `wap_alipay_vendor_id` IN ('$ids') OR `wap_qq_vendor_id` IN ('$ids') OR
                `wap_jd_vendor_id` IN ('$ids') OR `wap_baidu_vendor_id` IN ('$ids') OR `wap_union_vendor_id` IN ('$ids')")->get()->toArray();
            $notDelPays    = Pay::whereRaw("`vendor_id` IN ('$ids')")->get()->toArray();

            if ($notDelVendors || $notDelPays) {

                $tmpArr  = [];
                $tmpArr1 = [];

                foreach ($notDelVendors as $key => $val) {

                    array_push($tmpArr, $val['name'] . '(' . $val['no'] . ')');
                }

                foreach ($notDelPays as $key => $val) {

                    array_push($tmpArr1, $val['order_no']);
                }

                if ($tmpArr) {

                    $result['msg'] = '业务平台[' . implode(',', $tmpArr) . ']正在使用您删除的支付平台，';

                }

                if ($tmpArr1) {

                    $result['msg'] .= '您删除的支付平台下有支付账单[' . implode(',', $tmpArr1) . ']，';
                }

                $result['msg'] .= '操作被驳回';

            } else {

                $res = Vendor::destroy($ids);

                if ($res) {

                    $result['status'] = 0;
                    $result['msg']    = '删除成功';

                } else {

                    $result['msg'] = '删除失败';

                }
            }

        } else {

            $result['msg'] = '传入参数错误';

        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @api {get} /admin/vendors 查询支付平台
     * @apiName QueryVendors
     * @apiGroup Vendor
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function queryVendors(Request $request, Response $response, $args)
    {

        $getDatas = $request->getQueryParams();

        $page    = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 10;

        Paginator::currentPathResolver(function () {return "/admin/vendors";});
        Paginator::currentPageResolver(function () use ($page) {return $page;});

        $currentUserType = $this->jwt->userInfo->type;

        // 管理员看全部业务平台
        if ($currentUserType == 1) {

            $result = Vendor::orderBy('company_id', 'asc')->orderBy('pay_type', 'asc')->paginate($perPage);

        } else {

            $companiesArr = explode(',', $this->jwt->userInfo->company_ids);

            $result = Vendor::whereIn('company_id', $companiesArr)->orderBy('company_id', 'asc')->orderBy('pay_type', 'asc')->paginate($perPage);
        }

        $response->getBody()->write($result->toJson());

        return $response;
    }
}
