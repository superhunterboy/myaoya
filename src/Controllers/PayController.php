<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\OfflinePay;
use \Weiming\Models\Pay;
use \Weiming\Models\PaymentChannel;
use \Weiming\Models\Channel;

class PayController extends BaseController
{
    /**
     * @api {get} /admin/pays 查询支付账单列表
     * @apiName pays
     * @apiGroup Pay
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为 20
     * @apiParam {String} startime 开始时间
     * @apiParam {String} endtime 结束时间
     * @apiParam {String} account 会员账号
     * @apiParam {String} orderNo 订单号
     * @apiParam {String} vendorOrderNo 支付流水号
     * @apiParam {Number} status 支付状态: 0为成功支付记录，1为失败或放弃的支付记录，2为微信收款记录，3为支付宝收款记录
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 11,
     *               "company_id": 1,
     *               "platform_id": 1,
     *               "pay_out_type": 1,
     *               "pay_code": "1012",
     *               "amount": "2.01",                      // 金额
     *               "order_no": "201709091416013381700",   // 单号
     *               "platform_order_no": "",               // 流水号
     *               "status": 1,                           // 充值状态，1 失败、0 成功
     *               "remark": "",
     *               "created_at": null,                    // 充值时间
     *               "updated_at": null
     *           }
     *       ],
     *       "first_page_url": "/admin/getRecharges?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "/admin/getRecharges?page=1",
     *       "next_page_url": null,
     *       "path": "/admin/getRecharges",
     *       "per_page": 20,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *   }
     */
    public function queryPays(Request $request, Response $response, $args)
    {

        $getDatas = $request->getQueryParams();
        $page          = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage       = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 10;
        $status        = isset($getDatas['status']) ? intval($getDatas['status']) : 0; // 0 为成功支付记录，1 为失败或放弃的支付记录，2 为微信加好友收款记录，3 为支付宝加好友收款记录
        $vendorType    = isset($getDatas['vendorType']) ? intval($getDatas['vendorType']) : 0;
        $account       = isset($getDatas['account']) ? strval($getDatas['account']) : '';
        $orderNo       = isset($getDatas['orderNo']) ? strval($getDatas['orderNo']) : '';
        $startime      = isset($getDatas['startime']) ? strval($getDatas['startime']) : '';
        $endtime       = isset($getDatas['endtime']) ? strval($getDatas['endtime']) : '';
        $vendorOrderNo = isset($getDatas['vendorOrderNo']) ? strval($getDatas['vendorOrderNo']) : '';
        $down_excel    = isset($getDatas['down_excel']) ? strval($getDatas['down_excel']) : '';

        Paginator::currentPathResolver(function () {return "/admin/pays";});
        Paginator::currentPageResolver(function () use ($page) {return $page;});

        $currentUserType = $this->jwt->userInfo->type;

        $result = null;

        // 管理员看全部账单
        if ($currentUserType == 1) {
            $result = Pay::where('status', '=', $status);
        } else {
            $companiesArr = explode(',', $this->jwt->userInfo->company_ids);
            $result       = Pay::whereIn('company_id', $companiesArr)->where('status', '=', $status);
        }

        if ($orderNo) {
            $result = $result->where('order_no', '=', $orderNo);
        }
        if ($vendorOrderNo) {
            $result = $result->where('vendor_order_no', '=', $vendorOrderNo);
        }
        if ($account) {
            $result = $result->where('user', 'like', '%' . $account . '%');
        }
        if ($startime) {
            $result = $result->whereRaw("`created_at` > CONVERT_TZ('{$startime}', '-04:00', '+08:00')");
        }
        if ($endtime) {
            $result = $result->whereRaw("`created_at` < CONVERT_TZ('{$endtime}', '-04:00', '+08:00')");
        }
        if ($vendorType) {
            $result = $result->where('vendor_type', '=', $vendorType);
        }
        $result = $result->addSelect($this->db::raw("*, CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));

        if (empty($down_excel)) {
            $result = $result->orderBy('id', 'desc')->paginate($perPage)->toArray();
            foreach($result['data'] as $k=>$v){
                if((time()-strtotime($v['created_at']))>24*3600){
                    $result['data'][$k]['ishftrue']=1;      //超过24小时
                }else{
                    $result['data'][$k]['ishftrue']=2;      //24小时内
                }
                if(isset($v['version']) && $v['version'] == '2.0')
                {
                    $oPaymentChannel = PaymentChannel::find($v['vendor_type']);
                    $result['data'][$k]['platform']=$oPaymentChannel->platform;

                    $oChannel = Channel::find($v['pay_type']);
                    $result['data'][$k]['pay_type']=$oChannel->name;
                }
            }
            $result=(object)$result;
            //$result=json_encode($result);
            return $response->withJson($result);
        } else {
            $vendor_types = [
                "不可用","雅付", "闪付", "讯宝", "乐盈", "自由付", "沃雷特", "金海哲", "华仁", "荷包", "立刻付", "多多", "金海哲(新)", "仁信", "天付宝", "高通",
                "新雅付", "先行付", "我付", "汇达", "泽圣", "新自由付", "钱袋支付", "金阳", "个人支付宝", "旺富通", "千应", "优付", "商码付", "恒辰",
                "成沃", "开联通", "点付云", "芯富", "滕坤", "天吉", "众点", "智能云支付", "智能云支付2.0", "喜付", "艾付", "Nong付", "顺心付", "米达",
                "wpay", "恒星闪付", "众信", "星捷", "迅捷", "云通", "闪亿", "恒通", "Bingopay", "乐享", "易宝","随意付","畅支付","银信","五福","Pppay","顺达通","盖亚","1030","A付","AZ付","百盛","豪富","金融世家","VPay","广付通","联胜","个付","百汇","创优","NHH","善融","众鑫","大牛云闪付","盈付","万德","艾必德","稳付"
            ];
            $payTypes = [
                '网银', '微信', '支付宝', 'QQ钱包', '京东钱包', '百度钱包', '银联钱包', '微信WAP', '支付宝WAP', 'QQWAP', '京东WAP', '百度WAP', '银联WAP','云闪付','云闪付WAP'
            ];
            $result = $result->orderBy('id', 'DESC')->get()->toArray();
            $header = ['会员名称', '支付方式', '业务平台', '支付平台', '充值终端', '充值金额', '充值时间(当地)', '充值时间(美东)', '订单号', '支付流水号', '结果', '状态', '操作人'];
            $datas  = [];
            foreach ($result as $val) {
                $pay_type = '未知';
                foreach ($payTypes as $key => $value) {
                    if ($val['pay_type'] - 1 == $key) {
                        $pay_type = $value;
                    }
                }
                $compay = '未知';
                if ($val['company_id'] == '1') {
                    $compay = '澳亚';
                }
                $vendor_type = $vendor_types[$val['vendor_type']];
                $pc = '未知';
                if ($val['device'] == '1') {
                    $pc = 'pc端';
                } elseif ($val['device'] == '2') {
                    $pc = '移动端';
                }
                $state = '未处理';
                if (isset($val['rk_status'])) {
                    if ($val['rk_status'] == '1') {
                        $state = '已入款';
                    } elseif ($val['rk_status'] == '2') {
                        $state = '已忽略';
                    }
                }
                if(isset($val['version']) && $val['version'] == '2.0')
                {
                    $oPaymentChannel = PaymentChannel::find($val['vendor_type']);
                    $vendor_type=$oPaymentChannel->platform;

                    $oChannel = Channel::find($val['pay_type']);
                    $pay_type=$oChannel->name;
                }
                array_push($datas, [
                    0  => $val['user'] ?? '',
                    1  => $pay_type,
                    2  => $compay,
                    3  => $vendor_type,
                    4  => $pc,
                    5  => $val['money'] ?? '',
                    6  => $val['created_at'] ?? '',
                    7  => $val['created_at_edt'] ?? '',
                    8  => $val['order_no'] ?? '',
                    9  => $val['vendor_order_no'] ?? '',
                    10  => $val['recharge_msg'] ?? '',
                    11 => $state,
                    12 => $val['rk_user'] ?? ''
                ]);
            }

            $filename = '在线支付记录-' . date('Ymd') . '.xls';
            $this->downExcel($header, $datas, $filename); //父类继承
        }

    }

    /**
     * 入款操作
     */
    public function doPay(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $putDatas = $request->getParsedBody();
        $rkStatus = isset($putDatas['rk_status']) ? intval($putDatas['rk_status']) : 1;
        if($rkStatus == 3){
            $rkStatus=0;
        }
        if ($args['id'] > 0) {
            if($rkStatus == 111){
                $pay             = Pay::where('id', '=', $args['id'])->first();
                $pay->rk_status  = 1;
                $pay->recharge_msg  = "bbin已上分";
                $pay->recharge_status = 9;
                $pay->status = 0;
                $pay->rk_user_id = $this->jwt->userInfo->id;
                $pay->rk_user    = $this->jwt->userInfo->username;
                $res             = $pay->save();
            }else{
                $pay             = Pay::where('id', '=', $args['id'])->first();
                $pay->rk_status  = $rkStatus;
                $pay->rk_user_id = $this->jwt->userInfo->id;
                $pay->rk_user    = $this->jwt->userInfo->username;
                $res             = $pay->save();
            }
            if ($res) {

                $result['status'] = 0;
                $result['msg']    = '操作成功';
            }

        } else {

            $result['msg'] = '参数错误';

        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * 获取未处理支付记录数量
     */
    public function notOperatOrders(Request $request, Response $response, $args)
    {
        $success = Pay::where('status', '=', 0)->where('rk_status', '=', 0);
        $wechat  = Pay::where('status', '=', 2)->where('rk_status', '=', 0);
        $alipay  = Pay::where('status', '=', 3)->where('rk_status', '=', 0);
        $offline = OfflinePay::where('status', '=', 0);

        $currentUserType = $this->jwt->userInfo->type;

        // 管理员
        if ($currentUserType == 1) {
            $success = $success->count();
            $wechat  = $wechat->count();
            $alipay  = $alipay->count();
        } else {
            $companiesArr = explode(',', $this->jwt->userInfo->company_ids);
            $success      = $success->whereIn('company_id', $companiesArr)->count();
            $wechat       = $wechat->whereIn('company_id', $companiesArr)->count();
            $alipay       = $alipay->whereIn('company_id', $companiesArr)->count();
        }

        $offline = $offline->count();

        return $response->withJson([
            'status' => 0,
            'msg'    => '',
            'data'   => [
                'success' => $success,
                'wechat'  => $wechat,
                'alipay'  => $alipay,
                'offline' => $offline,
            ],
        ]);
    }
}
