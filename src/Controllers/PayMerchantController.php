<?php

namespace Weiming\Controllers;

use Illuminate\Pagination\Paginator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Payments\PingAn;
use Weiming\Models\MerQrcodePay;
use Weiming\Models\Merchant;

class PayMerchantController extends BaseController
{
    /**
     * @api {post/get} /checkOrder 更新商户支付记录,商家回掉接口(请勿操作)
     * @apiName CheckOrder
     * @apiGroup PayMerchant
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiSuccessExample {string} Success-Response:
     *   "notify_success"
     */
    public function checkOrder(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $postDatas = $request->getParsedBody();
        if ($getDatas && $postDatas) {
            $souseData = array_merge($getDatas, $postDatas);
        } elseif ($getDatas) {
            $souseData = $getDatas;
        } elseif ($postDatas) {
            $souseData = $postDatas;
        }

        $ord_no         = $getDatas['ord_no'] ?? '';
        $status         = $getDatas['status'] ?? '';
        $pay_time       = $getDatas['pay_time'] ?? 1;
        $money          = $getDatas['amount'] ?? 0;
        $trade_type     = $getDatas['trade_type'] ?? '';
        //日志
        $this->logger->addInfo('Pingan payment callback data:', $souseData);
        //验签
        $order = substr($ord_no, -6);
        $merOrder = MerQrcodePay::whereRaw("`order` like '%$order%'")->first();//var_dump($merOrder);exit;
        if ($merOrder) {
            $merchant_id = $merOrder->merchant_id;
            $merObj = Merchant::where('id', $merchant_id)->first();
            if ($merObj) {
                if (!empty($merObj->key)) {
                    $conf = json_decode($merObj->key, true);
                }
                if (!empty($merObj->open_id)) {
                    $conf['open_id'] = $merObj->open_id;
                }
                if (!empty($merObj->open_key)) {
                    $conf['open_key'] = $merObj->open_key;
                }
                $conf['shop_no'] = $merObj->shop_no;
                $bool = Pingan::getInstance($conf)->verifySign($getDatas);
            }
        }
        //入库
        if (isset($bool) && $bool) {
            /*if ($status == '9') {
                $status = '0';
            } elseif ($status == '4') {
                $status == '2';
            } elseif ($status == '2') {
                $status == '0';
            }*/
            $data['order']      = $ord_no;
            //$data['status']     = $status;
            $data['money']      = $money;
            $data['msg']        = '商户回掉支付状态:' . $status;
            $data['pay_time']   = strtotime($pay_time);
            if (MerQrcodePay::where('id', $merOrder->id)->update($data)) {
                return 'notify_success';
            }
        }
    }

    /**
     * @api {post} /addOrder 添加商户支付记录
     * @apiName AddOrder
     * @apiGroup PayMerchant
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} member 会员账号
     * @apiParam {String} order 交易单号
     * @apiParam {String} merchant_id 商户ID
     * @apiParam {Number} type 商户类型 1微信2 支付宝 默认1
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "成功",
     *       "data": {
     *           "member": "ajs8888",
     *           "order": "6584546",
     *           "merchant_id": "1",
     *           "updated_at": "2017-11-20 10:05:24",
     *           "created_at": "2017-11-20 10:05:24",
     *           "id": 1
     *       }
     *   }
     */
    public function addOrder(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $data['member']         = $postDatas['member'] ?? '';
        $data['order']          = $postDatas['order'] ?? '';
        $data['merchant_id']    = $postDatas['merchant_id'] ?? '';
        $data['type']           = $postDatas['type'] ?? '1';

        //日志
        $this->logger->addInfo('Merchant payment order data:', $postDatas);

        if (empty($data['member']) || empty($data['order']) || empty($data['merchant_id'])) {
            $result['msg'] = '缺少参数';
        }
        if($res = MerQrcodePay::create($data)) {
            $result['status'] = 0;
            $result['msg'] = '成功';
            $result['data'] = $res;
        } else {
            $result['msg'] = '添加失败';
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateOrder/:id 更新支付状态
     * @apiName UpdateOrder
     * @apiGroup PayMerchant
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码支付记录 ID
     *
     * @apiParam {Number} status 状态，0 未处理 1 已入款 2 忽略，默认为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": 1
     *   }
     */
    public function updateOrder(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id        = $args['id'] ?? 0;
        $postDatas = $request->getParsedBody();

        $status    = $postDatas['status'] ?? 0;
        $msg       = $postDatas['msg'] ?? '';

        $merCode = MerQrcodePay::where('id', '=', $id);
        $isPri = $merCode->first();
        if ($id > 0 && $isPri) {
            $data = ['user_id' => $this->jwt->userInfo->id];
            if ($msg) {
                $data['msg'] = $msg;
            }
            if ($status) {
                $data['status'] = $status;
            }
            if ($state = $merCode->update($data)) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
                $result['data']   = $state;
            }
        }else{
            $result['msg'] = '该条记录不存在!';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/PayMerchants 商户支付列表
     * @apiName PayMerchants
     * @apiGroup PayMerchant
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} type 支付类型,不提交默认为1 1:微信 2:支付宝
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数，默认为 20
     * @apiParam {String} key_word 关键字(会有账户,交易单号/商户订单号,商户编码,商户名称)
     * @apiParam {Number} startime 支付的起始时间
     * @apiParam {Number} endtime 支付的结束时间
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 1,
     *               "member": "ajs8888",
     *               "recharge_money": 0,
     *               "order": "6584546",
     *               "merchant_id": 1,
     *               "type": "1",
     *               "original_money": 0,
     *               "discount": 0,
     *               "money": 0,
     *               "hand_charge": 0,
     *               "status": "1",
     *               "user_id": 1,
     *               "msg": "",
     *               "created_at": "2017-11-20 10:05:24",
     *               "updated_at": "2017-11-20 10:12:47",
     *               "user": {
     *                   "id": 1,
     *                   "username": "admin",
     8                   "realname": "admin",
     *                   "type": 1,
     *                   "permissions": "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43",
     *                   "company_ids": "1,2",
     *                   "lastlogin": "2017-11-20 09:19:20",
     *                   "ip": "127.0.0.1",
     *                   "status": 1,
     8                   "created_at": "2017-02-24 09:16:21",
     *                   "updated_at": "2017-11-20 09:19:20"
     *               },
     *               "merchant": {
     *                   "id": 1,
     *                   "open_id": "txafCXQt058248b3230c9081ff90ce80",
     *                   "open_key": "aG0ck19g2HdthGRdSCfmiloOoGXoOzWZ",
     *                   "shop_no": "20260437",
     *                   "merchant_name": "Ran",
     *                   "signboard_name": "haha",
     *                   "address": "beijing",
     *                   "status": "1",
     *                   "type": "1",
     *                   "key": "",
     *                   "created_at": "2017-11-14 16:59:00",
     *                   "updated_at": "2017-11-18 14:26:15"
     *               }
     8           }
     *       ],
     *       "first_page_url": "/admin/PayMerchants?type=1&page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "/admin/PayMerchants?type=1&page=1",
     *       "next_page_url": null,
     *       "path": "/admin/PayMerchants?type=1",
     *       "per_page": 20,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *   }
     */
    public function PayMerchants(Request $request, Response $response, $args)
    {
        $getDatas   = $request->getQueryParams();
        $type       = $getDatas['type'] ?? '1';
        $down_excel = $getDatas['down_excel'] ?? '';
        $key_word   = $getDatas['key_word'] ?? null;
        $startime   = $getDatas['startime'] ?? null;
        $endtime    = $getDatas['endtime'] ?? null;
        $page       = $getDatas['page'] ?? 1;
        $perPage    = $getDatas['perPage'] ?? 20;

        Paginator::currentPathResolver(function () use ($type) {
            return "/admin/PayMerchants?type={$type}";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $payMers = MerQrcodePay::with('user')->with('merchant')->where('type', $type);

        if ($startime) {
            $payMers = $payMers->whereRaw("`created_at` >= CONVERT_TZ('{$startime}', '-04:00', '+08:00')");
        }
        if ($endtime) {
            $payMers = $payMers->whereRaw("`created_at` <= CONVERT_TZ('{$endtime}', '-04:00', '+08:00')");
        }

        if ($key_word) {
            $merchant = Merchant::whereRaw("shop_no LIKE '%{$key_word}%' or merchant_name LIKE '%{$key_word}%'")->first();
            if ($merchant) {
                $payMers = $payMers->where('merchant_id', $merchant->id);
            } else {
                $payMers = $payMers->whereRaw("`member` LIKE '%{$key_word}%' or `order` LIKE '%{$key_word}%'");
            }
        }

        $payMers = $payMers->addSelect($this->db::raw("*, CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));

        if (empty($down_excel)) {
            $priQrcodePay = $payMers->orderBy('id', 'DESC')->paginate($perPage);

            return $response->withJson($priQrcodePay);
        } else {
            $result = $payMers->orderBy('id', 'DESC')->get()->toArray();
            $header = ['会员账户','充值金额','交易单号','商户编号','商户名称','原始金额','优惠金额','实际交易','手续费','支付时间(当地)','支付时间(美东)','支付状态'];
            $datas =[];
            foreach ($result as $val) {
                $merNum  = $val['merchant']['shop_no'] ?? '';
                $merName = $val['merchant']['merchant_name'] ?? '';
                $state = '未处理';
                if (isset($val['status'])) {
                    if ($val['status'] == '1') {
                        $state = '已入款';
                    }elseif ($val['status'] == '2') {
                        $state = '已忽略';
                    }
                }
                array_push($datas, [
                    0  => $val['member'],
                    1  => $val['recharge_money'],
                    2  => $val['order'],
                    3  => $merNum,
                    4  => $merName,
                    5  => $val['original_money'],
                    6  => $val['discount'],
                    7  => $val['money'],
                    8  => $val['hand_charge'],
                    9  => $val['created_at'],
                    10  => $val['created_at_edt'],
                    11 => $state,
                ]);
            }
            if ($type == '1') {
                $filename = '微信商家码收款记录-'.date('Ymd').'.xls';
            }elseif ($type == '2') {
                $filename = '支付宝商家码收款记录-'.date('Ymd').'.xls';
            } else {
                $filename = '';
            }
            $this->downExcel($header, $datas, $filename); //父类继承
        }

    }
}
