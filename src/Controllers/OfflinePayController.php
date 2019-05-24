<?php

namespace Weiming\Controllers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Utils;
use Weiming\Models\BankCards;
use Weiming\Models\Company;
use Weiming\Models\OfflinePay;

class OfflinePayController extends BaseController
{
    /**
     * @api {post} /addOfflinePay 添加线下转款记录
     * @apiName AddOfflinePay
     * @apiGroup OfflinePay
     * @apiVersion 1.2.0
     * @apiPermission jwt
     *
     * @apiParam {String} account 会员账号
     * @apiParam {Float}  amount 金额
     * @apiParam {String} depositor 转款人/支付宝昵称
     * @apiParam {Number} card_id 银行卡id
     * @apiParam {Number} type 支付类型默认1 1:银行卡线下转账 2:支付宝转银行卡
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {//线下转账记录
     *       "status": 0,
     *       "msg": "提交成功",
     *       "data": []
     *   }
     *   {//支付宝添加记录
     *       "status": 0,
     *       "msg": "提交成功",
     *       "data": {
     *           "order_no": "201711161542412664241",
     *           "account": "ajs8888",
     *           "amount": "80",
     *           "depositor": "ran",
     *           "type": "2",
     *           "card_id": "14",
     *           "bank_name": "中国银行",
     *           "bank_card_no": "6227654878953654",
     *           "card_user": "ran",
     *           "updated_at": "2017-11-16 15:42:41",
     *           "created_at": "2017-11-16 15:42:41",
     *           "id": 55
     *       }
     *   }
     */
    public function add(Request $request, Response $response, $args)
    {
        $result     = ['status' => 1, 'msg' => '提交失败', 'data' => []];
        $postDatas  = $request->getParsedBody();
        $account    = $postDatas['account'] ?? '';
        $amount     = $postDatas['amount'] ?? 0;
        $depositor  = $postDatas['depositor'] ?? '';
        $card_id    = $postDatas['card_id'] ?? '';
        $type       = $postDatas['type'] ?? '1';
        $offlinePay = null;
        if ($account && $amount > 0 && $depositor) {
            /*$settings = Setting::select(['key', 'val'])
            ->where('key', '=', 'offline_pay_bank_name')
            ->orWhere('key', '=', 'offline_pay_bank_no')
            ->orWhere('key', '=', 'offline_pay_username')
            ->get()
            ->toArray();
            $tmp = [
            'offline_pay_bank_name' => '',
            'offline_pay_bank_no'   => '',
            'offline_pay_username'  => '',
            ];
            foreach ($settings as $setting) {
            $tmp[$setting['key']] = $setting['val'];
            }
            if ($type == '1') {
            $offlinePay = OfflinePay::create([
            'order_no'     => Utils::getOrderId(date('YmdHis')),
            'account'      => $account,
            'amount'       => $amount,
            'depositor'    => $depositor,
            'bank_name'    => $tmp['offline_pay_bank_name'],
            'bank_card_no' => $tmp['offline_pay_bank_no'],
            'card_user'    => $tmp['offline_pay_username'],
            ]);
            } elseif ($type == '2') {*/
            if ($card_id) {
                $card = BankCards::where('id', '=', $card_id)->first();
                if ($card) {
                    $offlinePay = OfflinePay::create([
                        'order_no'     => Utils::getOrderId(date('YmdHis')),
                        'account'      => $account,
                        'amount'       => $amount,
                        'depositor'    => $depositor,
                        'type'         => $type,
                        'card_id'      => $card_id,
                        'bank_name'    => $card->bank_name,
                        'bank_card_no' => $card->bank_number,
                        'card_user'    => $card->user_name,
                    ]);
                } else {
                    $result['msg'] = '木有此银行卡!';
                }
            } else {
                $result['msg'] = '缺少银行卡id!';
            }
        } else {
            $result['msg'] = '缺少必要参数!';
        }
        if ($offlinePay) {
            $result['status'] = 0;
            $result['msg']    = '提交成功';
            $result['data']   = $offlinePay;
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/offlinePays 线下转款列表
     * @apiName OfflinePays
     * @apiGroup OfflinePay
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} type 支付类型,不提交默认为 1:线下转账 2:支付宝转账
     * @apiParam {String} key_word 关键字: 会员账户/银行账号/转款人
     * @apiParam {number} status 状态: 状态 0 未处理 1 已入款 2 忽略  不传全部
     * @apiParam {String} startime 开始时间
     * @apiParam {String} endtime 结束时间
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数，默认为 20
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   { //线下银行
     *       "total": 5,
     *       "data": [
     *           {
     *               "id": 296,
     *               "order_no": "201711290927483492748",
     *               "account": "ceshi4",
     *               "amount": "1129.00",
     *               "depositor": "1129",
     *               "bank_name": "中国人民银行",
     *               "bank_card_no": "62589874563218546",
     *               "type": "1",
     *               "card_id": 0,
     *               "card_user": "阿拉亚",
     *               "status": 1,
     *               "remark": "",
     *               "user_id": 1,
     *               "created_at": "2017-11-29 09:27:48",
     *               "updated_at": "2017-12-01 17:37:46",
     *               "user": {
     *                   "id": 1,
     *                   "username": "admin",
     *                   "realname": "admin",
     *                   "type": 1,
     *                   "permissions": "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43",
     *                   "company_ids": "1,2",
     *                   "lastlogin": "2017-12-05 09:07:39",
     *                   "ip": "127.0.0.1",
     *                   "status": 1,
     *                   "created_at": "2017-02-24 09:16:21",
     *                   "updated_at": "2017-12-05 09:07:39"
     *               }
     *           }
     *       ]
     *   }
     *   {//支付宝转银行卡
     *       "total": 86,
     *       "data": [
     *           {
     *               "id": 256,
     *               "order_no": "201711270934562353456",
     *               "account": "ceshi4",
     *               "amount": "1123.00",
     *               "depositor": "ceshi4",
     *               "bank_name": "农业银行",
     *               "bank_card_no": "709870987609879922",
     *               "type": "2",
     *               "card_id": 19,
     *               "card_user": "张三",
     *               "status": 0,
     *               "remark": "",
     *               "user_id": 0,
     *               "created_at": "2017-11-27 09:34:56",
     *               "updated_at": "2017-11-27 09:34:56",
     *               "user": null
     *           }
     *        ]
     *   }
     */
    public function query(Request $request, Response $response, $args)
    {
      $time=time();
        $getDatas   = $request->getQueryParams();
        $type       = $getDatas['type'] ?? '1';
        $key_word   = $getDatas['key_word'] ?? '';
        $status     = $getDatas['status'] ?? '';
        $startime   = $getDatas['startime'] ?? '';
        $endtime    = $getDatas['endtime'] ?? '';
        $page       = $getDatas['page'] ?? 1;
        $perPage    = $getDatas['perPage'] ?? 20;
        $down_excel = $getDatas['down_excel'] ?? '';
        $offset     = ($page - 1) * $perPage;

        $offObj = OfflinePay::with('user')->with('bankCard')->where('type', $type);
        if ($status !== '') {
            $offObj = $offObj->where('status', '=', $status);
        }
        if ($startime) {
            $offObj = $offObj->whereRaw("`created_at` > CONVERT_TZ('{$startime}', '-04:00', '+08:00')");
        }
        if ($endtime) {
            $offObj = $offObj->whereRaw("`created_at` < CONVERT_TZ('{$endtime}', '-04:00', '+08:00')");
        }

        $offObj = $offObj->addSelect($this->db::raw("*, CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));
        $res    = $offObj->orderBy('id', 'DESC')->get();
        //->toArray()

        foreach ($res as $k=>$offObj) {
          $offObjarr[$k]['id'] = $offObj->id;
          $offObjarr[$k]['order_no'] = $offObj->order_no;
          $offObjarr[$k]['account'] = $offObj->account;
          $offObjarr[$k]['amount'] = $offObj->amount;
          $offObjarr[$k]['depositor'] = $offObj->depositor;
          $offObjarr[$k]['bank_name'] = $offObj->bank_name;
          $offObjarr[$k]['bank_card_no'] = $offObj->bank_card_no;
          $offObjarr[$k]['type'] = $offObj->type;
          $offObjarr[$k]['card_id'] = $offObj->card_id;
          $offObjarr[$k]['card_user'] = $offObj->card_user;
          $offObjarr[$k]['status'] = $offObj->status;
          $offObjarr[$k]['remark'] = $offObj->remark;
          $offObjarr[$k]['user_id'] = $offObj->user_id;
          $offObjarr[$k]['created_at'] = $offObj->created_at->format('Y-m-d h:i:s');
          $offObjarr[$k]['updated_at'] = $offObj->updated_at->format('Y-m-d h:i:s');
          $offObjarr[$k]['created_at_edt'] = $offObj->created_at_edt;
          $offObjarr[$k]['user'] = json_decode($offObj->user,true);
        }

        $result = [];
        if ($key_word) {
            foreach ($res as $key => $value) {
                if (preg_match("/$key_word/i", $value['account']) || preg_match("/$key_word/i", $value['bank_card_no']) || preg_match("/$key_word/i", $value['depositor'])) {
                    $result[] = $value;
                }
            }
        } else {
            $result = $res;
        }

        if (empty($down_excel)) {

            $data['total'] = count($result);
            $data['data']  = [];
            $range         = range($offset, $perPage * $page - 1);
            foreach ($result as $key => $value) {
                if (in_array($key, $range)) {
                    if((time()-strtotime($value['created_at']))>24*3600){
                        $value['ishftrue']=1;      //超过24小时
                    }else{
                        $value['ishftrue']=2;      //24小时内
                    }
                    $data['data'][] = $value;
                }
            }

            return $response->withJson($data);
        } else {
            if ($type == '1') {
                $header = ['订单号', '会员名称', '金额', '转款人', '入款银行', '入款账号', '入款户名', '充值时间(当地)', '充值时间(美东)', '备注', '状态', '操作人'];
                $datas  = [];
                foreach ($result as $val) {
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
                        0  => $val['order_no'],
                        1  => $val['account'],
                        2  => $val['amount'],
                        3  => $val['depositor'],
                        4  => $val['bank_name'],
                        5  => $val['bank_card_no'],
                        6  => $val['card_user'],
                        7  => $val['created_at'],
                        8  => $val['created_at_edt'],
                        9  => $val['remark'],
                        10  => $state,
                        11 => $user,
                    ]);
                }
            } elseif ($type == '2') {
                $header = ['会员账户', '存款银行', '银行账号', '充值金额', '支付宝呢称', '支付时间(当地)', '支付时间(美东)', '状态'];
                $datas  = [];
                foreach ($result as $value) {
                    //$user = $value['user']['username'] ?? '';
                    $state = '未处理';
                    if (isset($value['status'])) {
                        if ($value['status'] == '1') {
                            $state = '已入款';
                        } elseif ($value['status'] == '2') {
                            $state = '已忽略';
                        }
                    }
                    $bank_name   = '';
                    $bank_number = '';
                    if (isset($value['bank_card'])) {
                        $bank_name   = $value['bank_card']['bank_name'] ?? '';
                        $bank_number = $value['bank_card']['bank_number'] ?? '';
                    }
                    array_push($datas, [
                        0 => $value['account'],
                        1 => $bank_name,
                        2 => $bank_number,
                        3 => $value['amount'],
                        4 => $value['depositor'],
                        5 => $value['created_at'],
                        6 => $value['created_at_edt'],
                        7 => $state,
                        //10 => $user,
                    ]);
                }
            }
            if ($type == '1') {
                $filename = '线下转账汇款记录-' . date('Ymd') . '.xls';
            } elseif ($type == '2') {
                $filename = '支付宝转银行卡记录-' . date('Ymd') . '.xls';
            } else {
                $filename = '';
            }
            $this->downExcel($header, $datas, $filename); //父类继承
        }
    }

    /**
     * @api {post} /admin/updateOfflinePayStatus/:id 更新线下转款状态、备注
     * @apiName UpdateOfflinePayStatus
     * @apiGroup OfflinePay
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 线下转款记录 ID
     *
     * @apiParam {Number} type 支付类型,不提交默认为 1:线下转账 2:支付宝转账
     * @apiParam {Number} status 状态，0 未处理 1 已入款 2 忽略，默认为 0
     * @apiParam {String} remark 备注
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function updateStatus(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id        = $args['id'] ?? 0;
        $postDatas = $request->getParsedBody();
        $type      = $postDatas['type'] ?? '1';
        $status    = $postDatas['status'] ?? 0;
        $remark    = $postDatas['remark'] ?? '';
        $flag      = false;
        if ($id > 0) {
            $data = ['user_id' => $this->jwt->userInfo->id];
            if ($status > 0) {
                $data['status'] = $status;
                if ($status == 1) {
                    $flag = true;
                }
            }
            if ($remark) {
                $data['remark'] = $remark;
            }
            $offlinePay       = OfflinePay::where('id', '=', $id)->where('type', $type);
            $offlinePay       = $offlinePay->first();
            $orderNo          = $offlinePay->order_no;
            $offlinePayStatus = $offlinePay->status;
            $cardId           = $offlinePay->card_id;
            $amount           = $offlinePay->amount;
            // 加锁防止连续点击入款按钮
            $redisLockey = 'redisLock:' . $orderNo;
            $isLock      = $this->redisLock->lock($redisLockey, 120);
            $isLock=1;
            if ($isLock) {
                // 充值
                $company        = Company::where('no', '=', '00001')->first(); // 注：由于目前只有一个业务平台了，这里暂时写死为 00001 的业务平台
                $isAutorecharge = $company->is_autorecharge;
                $rechargeUrl    = $company->autorecharge_url;
                if ($status == 1 && $offlinePayStatus == 0 && $isAutorecharge == 1 && !empty($rechargeUrl)) {

                    // 只有 未处理 的记录，才执行入款操作
                    $requestParams = [
                        'account'      => $offlinePay->account,
                        'fee'          => $amount,
                        'orderNo'      => $orderNo,
                        'rechargeTime' => date('Y-m-d H:i:s'),
                    ];
                    $requestParams['sign']   = Utils::generateSignature($requestParams);
                    $requestParams['act']    = 'useRecharge';
                    $requestParams['remark'] = $type == 1 ? '线下银行卡转账' : ($type == 2 ? '支付宝转银行卡' : '');
                    $client                  = new Client();
                    $rechargeRes             = $client->request('POST', $rechargeUrl, ['form_params' => $requestParams]);die("====");
                    if ($rechargeRes->getStatusCode() == '200') {
                        $resData = $rechargeRes->getBody();
                        $resData = json_decode($resData, true);
                        $ret     = $resData['ret'];
                        $text    = $resData['text'];
                        // 备注原因
                        $data['remark'] = $text;
                        if ($ret == 1) {
                            // 成功将状态修改为 1 即 已入款
                            $data['status'] = 1;
                            $flag           = true;
                        } else {
                            // 未处理
                            $data['status'] = 0;
                        }
                    }
                }
                if($status == 3){
                    $data['status'] = 0;
                }
                $res = $offlinePay->update($data);
                if ($res) {
                    // Redis 解锁
                    $this->redisLock->unlock($redisLockey);
                    // 银行卡金额，次数递增
                    if ($flag) {
                        $bankCard = BankCards::where('id', '=', $cardId);
                        $bankCard->increment('count');
                        $bankCard->increment('money', $amount);
                    }
                    $result['status'] = 0;
                    $result['msg']    = '更新成功';
                    $result['data']   = $res;
                }
            } else {
                $result['msg'] = '请勿连续入款';
            }
        }
        return $response->withJson($result);
    }
}
