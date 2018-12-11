<?php

namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Models\OfflinePay;
use Weiming\Models\PriQrcodePay;

class AllManualController extends BaseController
{
    /**
     * @api {get} /admin/manuals 所有人工存入列表
     * @apiName Manuals
     * @apiGroup Manual
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} key_word 关键字: 会员账户
     * @apiParam {Number} status 支付状态 0未操作1已入款2已忽略 不传全部
     * @apiParam {String} startime 开始时间
     * @apiParam {String} endtime 结束时间
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数，默认为 20
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {"data":
     *      [{
     *          "id":36,
     *          "order_no":"201711020917061531706", 单号
     *          "account":"ajs8888", 会员账号
     *          "amount":"123.00", 转款金额
     *          "depositor":"11", 转款金额
     *          "bank_name":"\u4e2d\u56fd\u5efa\u8bbe\u94f6\u884c\u4e09\u5ce1\u652f\u884c", 线下转入银行名称
     *          "bank_card_no":"6226585475560012547", 线下转入银行卡号
     *          "type":"1", 类型 1线下 2支付宝转银行卡
     *          "card_id":0, 支付宝时 银行卡id
     *          "card_user":"\u674e\u5f66\u5b8f", 线下转入银行卡持有人
     *          "status":0, 状态 0 未处理 1 已入款 2 忽略
     *          "remark":"", 备注
     *          "user_id":0, 操作人id
     *          "created_at":"2017-11-02 09:17:06", 创建时间 下同
     *          "updated_at":"2017-11-02 09:17:06", 更新时间 下同
     *          "user":{
     *              "id":1,
     *              "username":"admin", 姓名
     *              "realname":"admin", 真名
     *              "type":1, 角色
     *              "permissions":"1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43", 权限
     *              "company_ids":"1,2", 所属平台
     *              "lastlogin":"2017-12-01 09:44:21", 最后登陆时间
     *              "ip":"127.0.0.1", 登陆地址
     *              "status":1, 状态 1激活 0禁用
     *              "created_at":"2017-02-24 09:16:21",
     *              "updated_at":"2017-12-01 09:44:21"
     *              }, 操作人信息
     *          "bank_card":{
     *              "id":3,
     *              "bank_name":"fdghdfg", 银行名称
     *              "user_name":"dfgdf", 银行卡持有人姓名
     *              "bank_number":"236844", 卡号
     *              "address":"ertfgjhghn", 银行卡开户行地址
     *              "status":"1", 使用状态 1使用 0禁用
     *              "created_at":"2017-11-17 16:55:21",
     *              "updated_at":"2017-11-19 18:29:33"
     *              } 支付宝银行卡信息
     *      }{
     *          "id":3,
     *          "member":"ran", 会员账号
     *          "money":0, 金额
     *          "drawee":"tt", 付款人(支付宝或者微信呢称)
     *          "qrcode_id":2, 二维码id
     *          "status":"1", 支付状态 0:未支付 1:已支付 2:忽略
     *          "user_id":1, 操作用户id
     *          "type":"2", 二维码类型 1微信 2支付宝
     *          "msg":"rty", 备注
     *          "created_at":"2017-11-17 15:21:29",
     *          "updated_at":"2017-11-21 14:35:15",
     *          "user":{
     *              "id":1,
     *              "username":"admin",
     *              "realname":"admin",
     *              "type":1,
     *              "permissions":"1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43",
     *              "company_ids":"1,2",
     *              "lastlogin":"2017-12-01 09:44:21",
     *              "ip":"127.0.0.1","status":1,
     *              "created_at":"2017-02-24 09:16:21",
     *              "updated_at":"2017-12-01 09:44:21"
     *              }, 同上
     *          "qrcode":{
     *              "id":2,
     *              "qrcode_name":"Ran", 微信号/支付宝账号(收款人)
     *              "url":"https:\/\/wm2028.oss-cn-hangzhou.aliyuncs.com\/0098cc805442dadb5b361d0a40b08938.png", 二维码地址
     *              "money":0, 收款总额
     *              "count":1, 支付次数
     *              "type":"2", 二维码类型1、微信 2、支付宝
     *              "status":"0", 使用状态0、不使用 1、使用
     *              "msg":"", 备注
     *              "created_at":"2017-11-14 15:07:02",
     *              "updated_at":"2017-11-21 14:35:15"
     *              }
     *      }],
     *      "total":51
     *   }
     */
    public function manuals(Request $request, Response $response, $args)
    {
        $getDatas   = $request->getQueryParams();
        $key_word   = $getDatas['key_word'] ?? '';
        $status     = $getDatas['status'] ?? '';
        $startime   = $getDatas['startime'] ?? '';
        $endtime    = $getDatas['endtime'] ?? '';
        $page       = $getDatas['page'] ?? 1;
        $perPage    = $getDatas['perPage'] ?? 20;
        $down_excel = $getDatas['down_excel'] ?? '';
        //偏移量
        $offset = ($page - 1) * ($perPage / 2);
        //页码数
        $perPage = $perPage / 2;

        $offObj = OfflinePay::with('user')->with('bankCard');
        $priObj = PriQrcodePay::with('user')->with('qrcode');

        if ($status !== '') {
            $offObj = $offObj->where('status', $status);
            $priObj = $priObj->where('status', $status);
        }
        if ($key_word) {
            $offObj = $offObj->whereRaw("account like '%{$key_word}%'");
            $priObj = $priObj->whereRaw("member like '%{$key_word}%'");
        }
        if ($startime) {
            $offObj = $offObj->whereRaw("`created_at` > CONVERT_TZ('{$startime}', '-04:00', '+08:00')");
            $priObj = $priObj->whereRaw("`created_at` > CONVERT_TZ('{$startime}', '-04:00', '+08:00')");
        }
        if ($endtime) {
            $offObj = $offObj->whereRaw("`created_at` < CONVERT_TZ('{$endtime}', '-04:00', '+08:00')");
            $priObj = $priObj->whereRaw("`created_at` < CONVERT_TZ('{$endtime}', '-04:00', '+08:00')");
        }
        //条数统计
        $offNom = $offObj->count();
        $priNom = $priObj->count();
        $total  = $offNom + $priNom;

        //默认数据
        $offOffset = $offset;
        $priOffset = $offset;
        $offPage   = $perPage;
        $priPage   = $perPage;

        //银行总页码
        $offPages = ceil($offNom / $perPage);
        //当前剩余页码量
        $offPageNow = $offNom - $perPage * $page;
        if (abs($offPageNow) < $perPage) {
            $offPageNow = $perPage + $offPageNow;
        } else {
            $offPageNow = 0;
        }
        //个人二维码总页码
        $priPages = ceil($priNom / $perPage);
        //当前剩余页码量
        $priPageNow = $priNom - $perPage * $page;
        if (abs($priPageNow) < $perPage) {
            $priPageNow = $perPage + $priPageNow;
        } else {
            $priPageNow = 0;
        }
        //判断银行偏移量和条数
        if ($offPages <= $page) {
            $offPage = $offPageNow;
            if ($offPages == $page) {
                $priPage = $perPage + ($perPage - $offPageNow);
            } else {
                $priPage = $perPage * 2;
            }
            if ($offPageNow == 0) {
                if ($priPages <= $page) {
                    $priOffset = 0;
                } else {
                    $differ    = $perPage * $offPages + ($perPage - $offNom % $perPage);
                    $priOffset = $differ + ($page - $offPages - 1) * ($perPage * 2);
                }
            }
        }
        //判断个人二维码偏移量和条数
        if ($priPages <= $page && $offPages > $page) {
            $priPage = $priPageNow;
            if ($priPages == $page) {
                $offPage = $perPage + ($perPage - $priPageNow);
            } else {
                $offPage = $perPage * 2;
            }
            if ($priPageNow == 0) {
                if ($offPages <= $page) {
                    $offOffset = 0;
                } else {
                    $differ    = $perPage * $priPages + ($perPage - $priNom % $perPage);
                    $offOffset = $differ + ($page - $priPages - 1) * ($perPage * 2);
                }

            }
        }

        $offObj = $offObj->addSelect($this->db::raw("*, CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));
        $priObj = $priObj->addSelect($this->db::raw("*, CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));

        //查询数据库
        $offlinePays = $offObj->orderBy('created_at', 'DESC');
        $priQrPays   = $priObj->orderBy('created_at', 'DESC');

        if (empty($down_excel)) {
            $offlinePays = $offlinePays->limit($offPage)->offset($offOffset);
            $priQrPays   = $priQrPays->limit($priPage)->offset($priOffset);
        }

        $offArr      = $offlinePays->get()->toArray();
        $priArr      = $priQrPays->get()->toArray();
        //$res =array_merge($offArr, $priArr);
        //格式化金额格式
        foreach ($priArr as $value) {
            $value['money'] = sprintf("%.2f", $value['money']);
            $pirvate[]      = $value;
        }
        unset($value);
        //组合数据
        foreach ($offArr as $value) {
            $pirvate[] = $value;
        }
        unset($value);
        $res               = $pirvate ?? [];
        $data['offOffset'] = $offOffset ?? '';
        $data['offPage']   = $offPage ?? '';
        $data['priOffset'] = $priOffset ?? '';
        $data['priPage']   = $priPage ?? '';
        //按时间排序
        $times = [];
        foreach ($res as $value) {
            $times[] = strtotime($value['created_at']);
        }
        unset($value);
        array_multisort($times, SORT_DESC, $res);

        if (empty($down_excel)) {
            $data['total'] = $total; //count($res);
            $data['data']  = $res;
            /*$range =range($offset, $perPage*$page-1);
            foreach ($res as $key => $value) {
            if (in_array($key, $range)) {
            $data['data'][]  = $value;
            }
            }*/

            return $response->withJson($data);
        } else {
            $header = ['会员账户', '充值金额', '存款人/呢称', '入款信息', '支付时间(当地)', '支付时间(美东)', '备注', '状态', '操作人'];
            $datas  = [];
            foreach ($res as $val) {
                $member = $val['account'] ?? $val['member'];
                $money  = $val['amount'] ?? $val['money'];
                $drawee = $val['depositor'] ?? $val['drawee'];
                $msg    = $val['remark'] ?? $val['msg'];
                if (!empty($val['order_no'])) {
                    if ($val['type'] == '1') {
                        $information = '线下银行转账:' . $val['card_user'] ?? '';
                    } elseif ($val['type'] == '2') {
                        $information = '支付宝银行转账:' . $val['card_user'] ?? '';
                    }
                } elseif (!empty($val['member'])) {
                    if ($val['type'] == '1') {
                        $information = '微信:' . $val['qrcode']['qrcode_name'] ?? '';
                    } elseif ($val['type'] == '2') {
                        $information = '支付宝:' . $val['qrcode']['qrcode_name'] ?? '';
                    } elseif ($val['type'] == '3') {
                        $information = 'QQ:' . $val['qrcode']['qrcode_name'] ?? '';
                    }
                }
                $user  = $val['user']['username'] ?? '';
                $state = '未处理';
                if (isset($val['status'])) {
                    if ($val['status'] == '1') {
                        $state = '已入款';
                    } elseif ($val['status'] == '2') {
                        $state = '已忽略';
                    }
                }
                array_push($datas, [
                    0 => $member ?? '',
                    1 => $money ?? '',
                    2 => $drawee ?? '',
                    3 => $information ?? '',
                    4 => $val['created_at'] ?? '',
                    5 => $val['created_at_edt'] ?? '',
                    6 => $msg ?? '',
                    7 => $state,
                    8 => $user,
                ]);
            }

            $filename = '人工存入记录-' . date('Ymd') . '.xls';
            $this->downExcel($header, $datas, $filename); //父类继承
        }
    }

}
