<?php

namespace Weiming\Controllers;

use \Carbon\Carbon;
use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Utils;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\Picture;
use \Weiming\Models\User;
use \Weiming\Models\Vendor;

class CompanyController extends BaseController
{
    /**
     * @api {post} /admin/companies 添加业务平台
     * @apiName AddCompany
     * @apiGroup Companie
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {String} no 平台编号
     * @apiParam {String} name 平台名字
     * @apiParam {String} url 支付前台地址
     * @apiParam {Number} wechat_vendor_id 微信支付平台id
     * @apiParam {Number} wap_wechat_vendor_id 微信wap支付平台id
     * @apiParam {Number} alipay_vendor_id 支付宝支付平台id
     * @apiParam {Number} wap_alipay_vendor_id 支付宝wap平台id
     * @apiParam {Number} netbank_vendor_id 银行支付平台id
     * @apiParam {Number} qq_vendor_id QQ支付平台id
     * @apiParam {Number} wap_qq_vendor_id QQwap支付平台id
     * @apiParam {Number} jd_vendor_id 京东支付平台id
     * @apiParam {Number} wap_jd_vendor_id 京东wap支付平台id
     * @apiParam {Number} baidu_vendor_id 百度支付平台id
     * @apiParam {Number} wap_baidu_vendor_id 百度wap支付平台id
     * @apiParam {Number} union_vendor_id 银联支付平台id
     * @apiParam {Number} wap_union_vendor_id 银联wap支付平台id
     * @apiParam {String} autorecharge_url 自动充值地址
     * @apiParam {Number} is_autorecharge 自动重试
     * @apiParam {Number} is_5qrcode 五码合一
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function addCompany(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $postDatas = $request->getParsedBody();

        if (empty($postDatas['name'])) {

            $result['msg'] = '业务平台名称不能为空';

        } else {

            // 验证业务平台名称唯一性
            $company = Company::where('name', '=', $postDatas['name'])->first();

            if (empty($company)) {

                $company = Company::create($postDatas);

                if ($company) {

                    // 生成业务平台编号，更新数据库
                    if (!isset($postDatas['no'])) {

                        Company::find($company->id)->update(['no' => str_pad($company->id, 5, 0, STR_PAD_LEFT)]);

                    }

                    // 创建微信、支付宝二维码图片
                    Picture::insert([
                        ['picture' => '', 'enabled' => 1, 'remark' => '', 'type' => 1, 'company_id' => $company->id, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
                        ['picture' => '', 'enabled' => 1, 'remark' => '', 'type' => 2, 'company_id' => $company->id, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
                        ['picture' => '', 'enabled' => 1, 'remark' => '', 'type' => 3, 'company_id' => $company->id, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
                    ]);

                    $result['status'] = 0;
                    $result['msg']    = '业务平台创建成功';
                    $result['data']   = $company;
                }

            } else {

                $result['msg'] = '业务平台名称已存在，请重新输入';

            }

        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @api {delete} /admin/companies/:id 删除业务平台
     * @apiName DeleteCompany
     * @apiGroup Companie
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function deleteCompany(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $ids = $request->getAttribute('id');

        if ($ids) {

            $ids = Utils::getIds($ids);

            // 判断业务平台是否有支付平台 and 是否有用户 and 是否有支付账单，有则不删除
            $notDelvendors = Vendor::whereIn('company_id', $ids)->get()->toArray();
            $notDelUsers   = User::whereRaw('1 != 1' . Utils::getRawSql(implode(',', $ids)))->get()->toArray();
            $notDelPays    = Pay::whereIn('company_id', $ids)->get()->toArray();

            if ($notDelvendors || $notDelUsers || $notDelPays) {

                $tmpArr1 = [];
                $tmpArr2 = [];
                $tmpArr3 = [];

                foreach ($notDelvendors as $key => $val) {

                    array_push($tmpArr1, $val['no']);
                }

                foreach ($notDelUsers as $key => $val) {

                    array_push($tmpArr2, $val['username'] . '(' . $val['id'] . ')');
                }

                foreach ($notDelPays as $key => $val) {

                    array_push($tmpArr3, $val['order_no']);
                }

                $result['msg'] = '为了数据的完整性，您删除的业务平台下有';

                if ($tmpArr1) {

                    $result['msg'] .= '支付平台[' . implode(',', $tmpArr1) . ']，';
                }

                if ($tmpArr2) {

                    $result['msg'] .= '用户[' . implode(',', $tmpArr2) . ']，';
                }

                if ($tmpArr3) {

                    $result['msg'] .= '支付账单[' . implode(',', $tmpArr3) . ']，';
                }

                $result['msg'] .= '操作被驳回';

            } else {

                $res = Company::destroy($ids);

                if ($res) {

                    // 删除微信、支付宝二维码图片
                    Picture::whereIn('company_id', $ids)->delete();

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
     * @api {put} /admin/companies/:id 更新业务平台
     * @apiName UpdateCompany
     * @apiGroup Companie
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {String} name 平台名字
     * @apiParam {String} url 支付前台地址
     * @apiParam {Number} wechat_vendor_id 微信支付平台id
     * @apiParam {Number} wap_wechat_vendor_id 微信wap支付平台id
     * @apiParam {Number} alipay_vendor_id 支付宝支付平台id
     * @apiParam {Number} wap_alipay_vendor_id 支付宝wap平台id
     * @apiParam {Number} netbank_vendor_id 银行支付平台id
     * @apiParam {Number} qq_vendor_id QQ支付平台id
     * @apiParam {Number} wap_qq_vendor_id QQwap支付平台id
     * @apiParam {Number} jd_vendor_id 京东支付平台id
     * @apiParam {Number} wap_jd_vendor_id 京东wap支付平台id
     * @apiParam {Number} baidu_vendor_id 百度支付平台id
     * @apiParam {Number} wap_baidu_vendor_id 百度wap支付平台id
     * @apiParam {Number} union_vendor_id 银联支付平台id
     * @apiParam {Number} wap_union_vendor_id 银联wap支付平台id
     * @apiParam {String} autorecharge_url 自动充值地址
     * @apiParam {Number} is_autorecharge 是否开启自动充值
     * @apiParam {Number} is_5qrcode 五码合一
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function updateCompany(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $putDatas = $request->getParsedBody();

        if (empty($putDatas['name'])) {

            $result['msg'] = '业务平台名称不能为空';

        } else {

            // 验证业务平台名称唯一性
            $company = Company::where('name', '=', $putDatas['name'])->where('id', '!=', $args['id'])->first();

            if (empty($company)) {

                $company = Company::find($args['id']);

                // 开启关闭自动入款时，先清空自动入款队列
                if ($company->is_autorecharge != $putDatas['is_autorecharge']) {
                    Resque::dequeue('default');
                }

                // $company->no = $putDatas['no'];
                $company->name                  = $putDatas['name'];
                $company->url                   = $putDatas['url'];
                $company->wechat_vendor_id      = $putDatas['wechat_vendor_id'];
                $company->wap_wechat_vendor_id  = $putDatas['wap_wechat_vendor_id'];
                $company->alipay_vendor_id      = $putDatas['alipay_vendor_id'];
                $company->wap_alipay_vendor_id  = $putDatas['wap_alipay_vendor_id'];
                $company->netbank_vendor_id     = $putDatas['netbank_vendor_id'];
                $company->qq_vendor_id          = $putDatas['qq_vendor_id'];
                $company->wap_qq_vendor_id      = $putDatas['wap_qq_vendor_id'];
                $company->jd_vendor_id          = $putDatas['jd_vendor_id'];
                $company->wap_jd_vendor_id      = $putDatas['wap_jd_vendor_id'];
                $company->baidu_vendor_id       = $putDatas['baidu_vendor_id'];
                $company->wap_baidu_vendor_id   = $putDatas['wap_baidu_vendor_id'];
                $company->union_vendor_id       = $putDatas['union_vendor_id'];
                $company->wap_union_vendor_id   = $putDatas['wap_union_vendor_id'];
                $company->autorecharge_url      = $putDatas['autorecharge_url'];
                $company->is_autorecharge       = $putDatas['is_autorecharge'];
                $company->is_5qrcode            = $putDatas['is_5qrcode'];
                $res                            = $company->save();

                if ($res) {

                    $result['status'] = 0;
                    $result['msg']    = '业务平台修改成功';

                }

            } else {

                $result['msg'] = '业务平台名称已存在，请重新输入';

            }

        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @api {get} /admin/companies 查询业务平台
     * @apiName QueryCompanies
     * @apiGroup Companie
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function queryCompanies(Request $request, Response $response, $args)
    {

        $getDatas = $request->getQueryParams();

        $page    = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 10;

        Paginator::currentPathResolver(function () {return "/admin/companies";});
        Paginator::currentPageResolver(function () use ($page) {return $page;});

        $currentUserType = $this->jwt->userInfo->type;

        // 管理员看全部业务平台
        if ($currentUserType == 1) {

            $result = Company::paginate($perPage);

        } else {

            $companiesArr = explode(',', $this->jwt->userInfo->company_ids);

            $result = Company::whereIn('id', $companiesArr)->paginate($perPage);
        }

        $response->getBody()->write($result->toJson());

        return $response;
    }

    /**
     * @api {get} /admin/getVendorsByCompanyId/:id/:type 当前业务平台下可用的支付平台
     * @apiName GetVendorsByCompanyId
     * @apiGroup Companie
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 业务平台id
     * @apiParam {Number} type 支付方式
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function getVendorsByCompanyId(Request $request, Response $response, $args)
    {

        $result    = ['status' => 1, 'msg' => '', 'data' => []];
        $companyId = $args['id'];
        $payType   = $args['type'];

        if ($companyId && $payType) {

            $where = '1 = 1';

            if ($payType == 1) {
                $where = '`wechat` = 1';
            } elseif ($payType == 2) {
                $where = '`alipay` = 1';
            } elseif ($payType == 3) {
                $where = '`netpay` = 1';
            } elseif ($payType == 4) {
                $where = '`qq` = 1';
            } elseif ($payType == 5) {
                $where = '`jd` = 1';
            } elseif ($payType == 6) {
                $where = '`baidu` = 1';
            } elseif ($payType == 7) {
                $where = '`union` = 1';
            } elseif ($payType == 8) {
                $where = '`wap_wechat` = 1';
            } elseif ($payType == 9) {
                $where = '`wap_alipay` = 1';
            } elseif ($payType == 10) {
                $where = '`wap_qq` = 1';
            } elseif ($payType == 11) {
                $where = '`wap_jd` = 1';
            } elseif ($payType == 12) {
                $where = '`wap_baidu` = 1';
            } elseif ($payType == 13) {
                $where = '`wap_union` = 1';
            } elseif ($payType == 14) {
                $where = '`yun` = 1';
            } elseif ($payType == 15) {
                $where = '`wap_yun` = 1';
            }

            $res = Vendor::where('company_id', '=', $companyId)->whereRaw($where)->get()->toArray();

            if ($res) {

                $result['status'] = 0;
                $result['msg']    = '有支付平台';
                $result['data']   = $res;

            } else {

                $result['msg'] = '没有支付平台，请添加支付平台';
            }

        } else {

            $result['msg'] = '参数错误';
        }

        return $response->withJson($result);
    }

    /**
     * @api {put} /admin/changeVendorByCompanyId/:id 业务平台切换支付平台
     * @apiName ChangeVendorByCompanyId
     * @apiGroup Companie
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 业务平台id
     * @apiParam {Number} wechat_vendor_id 微信支付平台id
     * @apiParam {Number} alipay_vendor_id 支付宝支付平台id
     * @apiParam {Number} netbank_vendor_id 网银支付平台id
     * @apiParam {Number} qq_vendor_id QQ支付平台id
     * @apiParam {Number} jd_vendor_id 京东支付平台id
     * @apiParam {Number} baidu_vendor_id 百度支付平台id
     * @apiParam {Number} union_vendor_id 银联支付平台id
     * @apiParam {Number} wap_wechat_vendor_id 微信wap支付平台id
     * @apiParam {Number} wap_alipay_vendor_id 支付宝wap支付平台id
     * @apiParam {Number} wap_qq_vendor_id QQwap支付平台id
     * @apiParam {Number} wap_jd_vendor_id 京东wap支付平台id
     * @apiParam {Number} wap_baidu_vendor_id 百度wap支付平台id
     * @apiParam {Number} wap_union_vendor_id 银联wap支付平台id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *
     */
    public function changeVendorByCompanyId(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $companyId = $args['id'];

        $putDatas = $request->getParsedBody();

        // $vendorId        = isset($putDatas['vendor_id']) ? intval($putDatas['vendor_id']) : -1;
        $wechatVendorId     = isset($putDatas['wechat_vendor_id']) ? intval($putDatas['wechat_vendor_id']) : -1;
        $alipayVendorId     = isset($putDatas['alipay_vendor_id']) ? intval($putDatas['alipay_vendor_id']) : -1;
        $netbankVendorId    = isset($putDatas['netbank_vendor_id']) ? intval($putDatas['netbank_vendor_id']) : -1;
        $qqVendorId         = isset($putDatas['qq_vendor_id']) ? intval($putDatas['qq_vendor_id']) : -1;
        $jdVendorId         = isset($putDatas['jd_vendor_id']) ? intval($putDatas['jd_vendor_id']) : -1;
        $baiduVendorId      = isset($putDatas['baidu_vendor_id']) ? intval($putDatas['baidu_vendor_id']) : -1;
        $unionVendorId      = isset($putDatas['union_vendor_id']) ? intval($putDatas['union_vendor_id']) : -1;
        $yunVendorId      = isset($putDatas['yun_vendor_id']) ? intval($putDatas['yun_vendor_id']) : -1;
        $wapWechatVendorId  = isset($putDatas['wap_wechat_vendor_id']) ? intval($putDatas['wap_wechat_vendor_id']) : -1;
        $wapAlipayVendorId  = isset($putDatas['wap_alipay_vendor_id']) ? intval($putDatas['wap_alipay_vendor_id']) : -1;
        $wapQqVendorId      = isset($putDatas['wap_qq_vendor_id']) ? intval($putDatas['wap_qq_vendor_id']) : -1;
        $wapJdVendorId      = isset($putDatas['wap_jd_vendor_id']) ? intval($putDatas['wap_jd_vendor_id']) : -1;
        $wapBaiduVendorId   = isset($putDatas['wap_baidu_vendor_id']) ? intval($putDatas['wap_baidu_vendor_id']) : -1;
        $wapUnionVendorId   = isset($putDatas['wap_union_vendor_id']) ? intval($putDatas['wap_union_vendor_id']) : -1;
        $wapYunVendorId   = isset($putDatas['wap_yun_vendor_id']) ? intval($putDatas['wap_yun_vendor_id']) : -1;

        if ($companyId > 0) {

            $res = Company::find($companyId);

            $updateDataArr = [];
            $errorVendorId = 0;

            if ($wechatVendorId >= 0) {
                $updateDataArr = ['wechat_vendor_id' => $wechatVendorId];
                $errorVendorId = $res->wechat_vendor_id;
            } elseif ($alipayVendorId >= 0) {
                $updateDataArr = ['alipay_vendor_id' => $alipayVendorId];
                $errorVendorId = $res->alipay_vendor_id;
            } elseif ($netbankVendorId >= 0) {
                $updateDataArr = ['netbank_vendor_id' => $netbankVendorId];
                $errorVendorId = $res->netbank_vendor_id;
            } elseif ($qqVendorId >= 0) {
                $updateDataArr = ['qq_vendor_id' => $qqVendorId];
                $errorVendorId = $res->qq_vendor_id;
            } elseif ($jdVendorId >= 0) {
                $updateDataArr = ['jd_vendor_id' => $jdVendorId];
                $errorVendorId = $res->jd_vendor_id;
            } elseif ($baiduVendorId >= 0) {
                $updateDataArr = ['baidu_vendor_id' => $baiduVendorId];
                $errorVendorId = $res->baidu_vendor_id;
            } elseif ($unionVendorId >= 0) {
                $updateDataArr = ['union_vendor_id' => $unionVendorId];
                $errorVendorId = $res->union_vendor_id;
            } elseif ($wapWechatVendorId >= 0) {
                $updateDataArr = ['wap_wechat_vendor_id' => $wapWechatVendorId];
                $errorVendorId = $res->wap_wechat_vendor_id;
            } elseif ($wapAlipayVendorId >= 0) {
                $updateDataArr = ['wap_alipay_vendor_id' => $wapAlipayVendorId];
                $errorVendorId = $res->wap_alipay_vendor_id;
            } elseif ($wapQqVendorId >= 0) {
                $updateDataArr = ['wap_qq_vendor_id' => $wapQqVendorId];
                $errorVendorId = $res->wap_qq_vendor_id;
            } elseif ($wapJdVendorId >= 0) {
                $updateDataArr = ['wap_jd_vendor_id' => $wapJdVendorId];
                $errorVendorId = $res->wap_jd_vendor_id;
            } elseif ($wapBaiduVendorId >= 0) {
                $updateDataArr = ['wap_baidu_vendor_id' => $wapBaiduVendorId];
                $errorVendorId = $res->wap_baidu_vendor_id;
            } elseif ($wapUnionVendorId >= 0) {
                $updateDataArr = ['wap_union_vendor_id' => $wapUnionVendorId];
                $errorVendorId = $res->wap_union_vendor_id;
            } elseif ($wapYunVendorId >= 0) {
                $updateDataArr = ['wap_yun_vendor_id' => $wapYunVendorId];
                $errorVendorId = $res->wap_yun_vendor_id;
            } elseif ($yunVendorId >= 0) {
                $updateDataArr = ['yun_vendor_id' => $yunVendorId];
                $errorVendorId = $res->yun_vendor_id;
            }

            // 记录支付平台错误次数
            $errorVendor = Vendor::find($errorVendorId);

            if ($errorVendor) {
                $errorVendor->update(['error_count' => $errorVendor->error_count + 1]);
            }
            // 切换支付平台
            $res = $res->update($updateDataArr);
            if ($res) {

                $result['status'] = 0;
                $result['msg']    = '支付平台已切换成功';

            } else {

                $result['msg'] = '支付平台已切换失败';
            }

        } else {

            $result['msg'] = '参数错误';
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * 通过当前用户所属业务平台id获取业务平台
     */
    public function getCompaniesByCurrentUser(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $currentUserCompanyIds = $this->jwt->userInfo->company_ids;

        $currentUserType = $this->jwt->userInfo->type;

        $companyIdsArr = explode(',', $currentUserCompanyIds);

        if ($companyIdsArr) {

            $res = [];

            if ($currentUserType == 1) {

                $res = Company::all()->toArray();

            } else {

                $res = Company::whereIn('id', $companyIdsArr)->get()->toArray();

            }

            if ($res) {

                $result['status'] = 0;
                $result['msg']    = '有业务平台数据';
                $result['data']   = $res;
            } else {

                $result['msg'] = '无业务平台数据';
            }

        } else {

            $result['msg'] = '参数错误';
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }
}
