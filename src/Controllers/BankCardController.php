<?php

namespace Weiming\Controllers;

use Illuminate\Pagination\Paginator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Models\BankCards;
use Weiming\Models\OfflinePay;
use Weiming\Models\Setting;
use Weiming\Models\Level;
use Weiming\Models\Member;

class BankCardController extends BaseController
{
    /**
     * @api {get} /getBankCard 获取收款银行卡信息
     * @apiName GetBankCard
     * @apiGroup BankCards
     * @apiVersion 1.1.0
     * @apiPermission none
     *
     * @apiParam {Number} level_id 层级id 支付宝不传值 线下必传
     * @apiParam {Number} type 银行卡类型 1 支付宝  2 线下
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "成功",
     *       "data": {
     *           "id": 3,
     *           "bank_name": "东亚银行",
     *           "user_name": "dfgdf",
     *           "bank_number": "4572154",
     *           "address": "ertfgjhghn",
     *           "type": "1",
     *           "level_ids": null,
     *           "count": 0,
     *           "money": "0.00",
     *           "status": "1",
     *           "created_at": "2017-11-17 16:55:21",
     *           "updated_at": "2017-11-17 18:50:08"
     *       }
     *   }
     */
    public function getBankCard(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '获取失败!', 'data' => []];
        $getDatas  = $request->getQueryParams();
        $level_id  = $getDatas['level_id'] ?? '';
        $type      = $getDatas['type'] ?? '1';

        $bankObj = BankCards::where('status', '1');
        //获取线下转账银行卡信息
        if (!empty($level_id)) {
            if ($type == '1') {
                $type = '2';
            }
            $res = $bankObj->where('type', $type)->whereRaw("find_in_set($level_id, level_ids)")->first();
            if ($res) {
                $result['status'] = 0;
                $result['msg']    = '成功';
                $result['data']   = $res;
            }
        } else {
            $setting = Setting::where('key', 'alipay_single_qrcode');
            $state   = explode(',', $setting->value('val'));
            if (!in_array('1', $state)) {
                $result['msg'] = '未开启银行卡!';
                return $response->withJson($result);
            }

            foreach ($bankObj->where('type', $type)->get() as $key => $value) {
                $banks[] = $value->bank_name;
            }
            if (isset($banks) && is_array($banks) && count($banks) > 0) {
                $bankStr = "'";
                $bankStr .= implode("','", $banks);
                $bankStr .= "'";
                $bank = $bankObj->whereRaw("`bank_name` IN ({$bankStr})")->get();
            }else{
                $result['msg'] = '没有银行卡处于使用状态';
                return $response->withJson($result);
            }
            if($bank) {
                $result['status']       = 0;
                $result['msg']          = '成功';
                $result['data']         = $bank;
            }else{
                $result['msg'] = '获取银行信息失败';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/addBankCard 添加银行卡
     * @apiName AddBankCard
     * @apiGroup BankCards
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {String} bank_name 银行名称
     * @apiParam {String} user_name 银行卡户名
     * @apiParam {String} bank_number 银行卡号
     * @apiParam {String} address 开户行地址
     * @apiParam {Number} type 银行类型 1、支付宝 2、线下 默认1
     * @apiParam {String} level_ids 线下银行卡层级id 格式 1,2,3...
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功",
     *       "data": {
     *           "bank_name": "中国人民银行",
     *           "user_name": "Ranch",
     *           "bank_number": "6227008532658978",
     *           "address": "中国/重庆",
     *           "type": "2",
     *           "level_ids": "2，3，5，8",
     *           "updated_at": "2017-12-09 13:34:51",
     *           "created_at": "2017-12-09 13:34:51",
     *           "id": 14
     *       }
     *   }
     */
    public function addBankCard(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '添加失败', 'data' => []];
        $postDatas      = $request->getParsedBody();
        $bank_name      = $postDatas['bank_name'] ?? '';
        $user_name      = $postDatas['user_name'] ?? '';
        $bank_number    = $postDatas['bank_number'] ?? '';
        $address        = $postDatas['address'] ?? '';
        $type           = $postDatas['type'] ?? '1';
        $level_ids      = $postDatas['level_ids'] ?? '';

        $bank = BankCards::where('type', $type)->where('bank_number', $bank_number)->first();
        if($bank){
            $result['msg'] = '银行卡号重复';
            return $response->withJson($result);
        }

        if (empty($level_ids)) {
            $result['msg'] = '层级错误!';
            return $response->withJson($result);
        }
        $levelArr = explode(',', $level_ids);
        $where = '`type` = ' . $type . ' and ';
        $tmp = [];
        foreach ($levelArr as $value) {
            array_push($tmp, 'find_in_set(' . $value . ', `level_ids`)');
        }
        $tmp = '(' . implode(' or ', $tmp) . ')';
        $bankObj = BankCards::whereRaw($where . $tmp)->count();
        unset($value);
        if($bankObj){
            $result['msg'] = '层级冲突!';
            return $response->withJson($result);
        }

        if (!empty($bank_name) && !empty($user_name) && !empty($bank_number)) { // && !empty($address)
            $status = '0';
            if ($type == '2') {
                $status = '1';
            }
            if ($res = BankCards::create([
                'bank_name'     => $bank_name,
                'user_name'     => $user_name,
                'bank_number'   => $bank_number,
                'address'       => $address,
                'type'          => $type,
                'level_ids'     => $level_ids,
                'status'        => $status,
            ])) {
                $result['status'] = 0;
                $result['msg']    = '添加成功';
                $result['data']   = $res;
            }
            if ($res) {
                foreach ($levelArr as  $value) {
                    Level::where('id', $value)->update(['status'=>'1']);
                }

            }
        }else{
            $result['msg'] = '缺少必要参数';
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/addBankCards 批量添加银行卡
     * @apiName AddBankCards
     * @apiGroup BankCards
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     *
     * @apiParam {Json} banks 银行信息 bank_name 银行名称,user_name 银行卡户名,bank_number 银行卡号,address 开户行地址.  示例[{"bank_name":"...","user_name":"...","bank_number":"...","address":"..."},...]
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "成功",
     *       "data": {
     *           "success": {
     *               "msg": "银行卡:236844,123574,添加成功!",
     *               "count": 2
     *           },
     *           "error": {
     *               "msg": "银行卡:添加失败",
     *               "count": 0
     *           }
     *       }
     *   }
     */
    public function addBankCards(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '添加失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $banks     = json_decode($postDatas['banks'], true);

        if (!is_array($banks)) {
            $result['msg'] = '参数格式错误';
            return $response->withJson($result);
        }
        $unique = [];
        foreach ($banks as $key => $value) {
            $unique = array_merge($unique, [$value['bank_number']]);
        }
        $isUnique = array_unique($unique);
        if (count($isUnique) < count($unique)) {
            $result['msg'] = '添加银行卡中卡号重复';
            return $response->withJson($result);
        }

        $uniqueStr = implode(',', $unique);
        $bank = BankCards::whereRaw("bank_number IN ($uniqueStr)")->first();
        if($bank){
            $result['msg'] = '银行卡与原有卡号重复';
           return $response->withJson($result);
        }

        $success = ['msg'=>'银行卡:', 'count'=>0];
        $error   = ['msg'=>'银行卡:', 'count'=>0];
        foreach ($banks as $key => $value) {
            if (BankCards::create([
                'bank_name'     => $value['bank_name'],
                'user_name'     => $value['user_name'],
                'bank_number'   => $value['bank_number'],
                'address'       => $value['address'],
                //'status'              => $status,
            ])) {
                $success['msg'] .= $value['bank_number'] . ',';
                $success['count']++;
            } else {
                $error['msg'] .= $value['bank_number'] . ',';
                $error['count']++;
            }
        }
        $success['msg'] .= '添加成功!';
        $error['msg']   .= '添加失败';

        if ($success['count'] > 1) {
            $result['msg']    = '成功';
            $result['status'] = 0;
            $result['data']['success']   = $success;
            $result['data']['error']     = $error;
        }else{
            $result['data']['success']   = $success;
            $result['data']['error']     = $error;
        }

        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateBankCards 批量更新银行卡
     * @apiName UpdateBankCards
     * @apiGroup BankCards
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     *
     * @apiParam {Json} banks 银行信息 id 银行卡id,bank_name 银行名称,user_name 银行卡户名,bank_number 银行卡号,address 开户行地址.  示例[{"id":"...","bank_name":"...","user_name":"...","bank_number":"...","address":"..."},...] 备注:有id字段更新,无id字段创建
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function updateBankCards(Request $request, Response $response, $args)
    {
        $result    = ['status' => 0, 'msg' => '更新成功', 'data' => []];
        $postDatas = $request->getParsedBody();
        $banks     = json_decode($postDatas['banks'], true);

        if (!is_array($banks)) {
            $result['status'] = 1;
            $result['msg'] = '参数格式错误';
            return $response->withJson($result);
        }
        //更新卡号唯一
        $updateUnique = [];
        foreach ($banks as $key => $value) {
            $updateUnique = array_merge($updateUnique, [$value['bank_number']]);
            if (isset($value['status'])) {
                $result['status'] = 1;
                $result['msg'] = '不能批量操作时更改使用状态';
                return $response->withJson($result);
            }
        }
        $isUnique = array_unique($updateUnique);
        if (count($isUnique) < count($updateUnique)) {
            $result['status'] = 1;
            $result['msg'] = '更新银行卡中卡号重复';
            return $response->withJson($result);
        }
        //批量创建时卡号唯一
        $allUnique   = [];
        $createBanks = [];
        $ids        = [];
        foreach ($banks as $key => $value) {
            if (empty($value['id'])) {
                $allUnique   = array_merge($allUnique, [$value['bank_number']]);
                $createBanks = array_merge($createBanks, $banks[$key]);
                unset($banks[$key]);
            } else {
                array_push($ids, $value['id']);
            }
        }
        $uniqueStr = "'";
        $uniqueStr .= implode(',', $allUnique);
        $uniqueStr .= "'";
        $bank = BankCards::whereRaw("bank_number IN ($uniqueStr)")->first();
        if($bank){
            $result['status'] = 1;
            $result['msg'] = '创建银行卡号重复';
           return $response->withJson($result);
        }

        //更新
        if (isset($ids) && count($ids) > 0) {
            $idStr = "'";
            $idStr .= implode(',', $ids);
            $idStr .= "'";
        }
        foreach ($banks as $key => $value) {
                $id = $value['id'];
                if (empty($id)) {
                    $result['status'] = 1;
                    $result['msg'] = '更新银行卡失败';
                    return $response->withJson($result);
                    break;
                }
                unset($value['id']);
            if(OfflinePay::whereRaw("card_id IN ($idStr)")->count() == 0){
                if (!BankCards::where('id', $id)->update($value)) {
                    $result['status'] = 1;
                    $result['msg'] = '更新银行卡失败';
                    return $response->withJson($result);
                    break;
                }
            } else {
                $result['status'] = 1;
                $result['msg'] = 'id:'.$id.',银行卡有支付记录,更新银行卡失败';
                return $response->withJson($result);
                break;
            }
        }
         //创建
         if (count($createBanks) > 0) {
            if (!BankCards::create($createBanks)) {
                $result['status'] = 1;
                $result['msg'] = '添加银行卡失败';
                return $response->withJson($result);
            }
         }



        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateBankCard/:id 更新银行卡
     * @apiName UpdateBankCard
     * @apiGroup BankCards
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 银行卡id
     * @apiParam {String} bank_name 银行名称
     * @apiParam {String} bank_number 银行卡号
     * @apiParam {String} user_name 银行卡户名
     * @apiParam {String} address 开户行地址
     * @apiParam {Number} status 使用状态 1:使用 0:不使用
     * @apiParam {Number} type 银行卡类型 1支付宝 2线下 默认1
     * @apiParam {String} level_ids 线下银行卡层级id 格式 1,2,3...
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "信息更新成功",
     *       "data": 1
     *   }
     */
    public function updateBankCard(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id        = $args['id'] ?? '';
        $postDatas = $request->getParsedBody();

        $bank_name      = $postDatas['bank_name'] ?? '';
        $user_name      = $postDatas['user_name'] ?? '';
        $bank_number    = $postDatas['bank_number'] ?? '';
        $address        = $postDatas['address'] ?? '';
        $status         = $postDatas['status'] ?? '';
        $type           = $postDatas['type'] ?? '1';
        $level_ids      = $postDatas['level_ids'] ?? '';

        if (!empty($id)) {
            if ($bank_name != '') {
                $data['bank_name'] = $bank_name;
            }
            if ($user_name != '') {
                $data['user_name'] = $user_name;
            }
            if ($bank_number != '') {
                $data['bank_number'] = $bank_number;
            }
            //if ($address != '') {
                $data['address'] = $address;
            //}
            $levelArr = '';
            if ($level_ids != '' && $bank_name != '') {
                $levelArr = explode(',', $level_ids);
                if (!is_array($levelArr) || count($levelArr) < 1) {
                    $result['msg'] = '层级错误!';
                    return $response->withJson($result);
                }

                $where = '`type` = ' . $type . ' and `id` != ' . $id . ' and ';
                $tmp = [];
                foreach ($levelArr as $value) {
                    array_push($tmp, 'find_in_set(' . $value . ', `level_ids`)');
                }
                $tmp = '(' . implode(' or ', $tmp) . ')';
                $bankObj = BankCards::whereRaw($where . $tmp)->count();
                unset($value);
                if($bankObj){
                    $result['msg'] = '层级冲突!';
                    return $response->withJson($result);
                }
                $data['level_ids'] = $level_ids;
            } elseif ($level_ids == '' && $bank_name != '') { //禁用银行卡前，清空level
                $data['level_ids'] = '';
            }
            if ($status !== '' && in_array($status, [0, 1])) {
                if ($status == 1 && $bank_name && $type == '1') {
                    $bankInfo = BankCards::where('type', $type)->where('bank_name', $bank_name)->where('status', '1');

                    $bankInfo->where('type', $type)->where('bank_name', $bank_name)->update(['status' => '0']);
                }
                $data['status'] = $status;
            }
            //原来level
            $oldLevel = BankCards::where('id', $id)->value('level_ids');
            if (empty($level_ids) && !empty($oldLevel) && $status == '0') {
                $result['msg'] = '银行卡占用层级,不能禁用!';

                return $response->withJson($result);
            } elseif (empty($level_ids) && !empty($bank_name)) {
                $level_ids = '';
            } elseif (empty($level_ids) && empty($bank_name)) {
                $level_ids = $oldLevel;
            }
            if(OfflinePay::where('card_id', '=', $id)->count() == 0){
                $state = BankCards::where('id', '=', $id)->update($data);
                if ($state) {
                    $result['status'] = 0;
                    $result['msg']    = '信息更新成功';
                    $result['data']   = $state;
                }
            } else {
                $state = BankCards::where('id', '=', $id)->update(['status'=>$status, 'level_ids'=>$level_ids, 'address'=>$address]);
                if ($state) {
                    $result['status'] = 0;
                    $result['msg']    = '状态更新成功';//['status'=>$status, 'level_ids'=>$level_ids]
                    $result['data']   = $state;
                }
            }
            //else{
            //    $result['msg'] = '该银行卡下有支付记录，您不能更新银行信息';
            //}
        } else {
            $result['msg'] = '缺少参数!';
        }
        if ($state) { //更新对应的层级
            $oldLevelArr = explode(',', $oldLevel);
            foreach ($oldLevelArr as $value) {
                Level::where('id', $value)->update(['status'=>'0']);
            }
            unset($value);
            if (isset($levelArr) && is_array($levelArr)) {
                foreach ($levelArr as $value) {
                    Level::where('id', $value)->update(['status'=>'1']);
                }
                unset($value);
            }

        }

        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/bankCards 银行卡列表
     * @apiName BankCards
     * @apiGroup BankCards
     * @apiVersion 1.1.1
     * @apiPermission jwt
     *
     * @apiParam {Number} type 银行卡类型 1支付宝 2线下 默认1
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *       { //支付宝
     *           "id": 1,
     *           "bank_name": "东亚银行",
     *           "user_name": "张三",
     *           "bank_number": "195497254765778572",
     *           "address": "beijing",
     *           "type": "1",
     *           "level_ids": null,
     *           "count": 0,
     *           "money": "0.00",
     *           "status": "1",
     *           "created_at": "2017-11-16 11:41:38",
     *           "updated_at": "2017-11-16 11:41:38"
     *       },
     *   ]
     *   "total": 1,
     *   "data":
     *   [
     *       { //线下
     *           "id": 14,
     *           "bank_name": "中国人民银行",
     *           "user_name": "Ranch",
     *           "bank_number": "6227008532658978",
     *           "address": "中国/重庆",
     *           "type": "2",
     *           "level_ids": "2，3，5，8",
     *           "count": 0,
     *           "money": "0.00",
     *           "status": "0",
     *           "created_at": "2017-12-09 13:34:51",
     *           "updated_at": "2017-12-09 13:34:51"
     *       }
     *   ]
     */
    public function bankCards(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $type     = $getDatas['type'] ?? '1';
        $page     = $getDatas['page'] ?? 1;
        $perPage  = $getDatas['perPage'] ?? 20;
        $offset   = ($page-1) * $perPage;

        /*Paginator::currentPathResolver(function () use ($type) {
            return "/admin/bankCards?type={$type}";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });*/

        $bankObj =BankCards::where('type', $type)->orderBy('status', 'desc')->orderBy('created_at', 'desc');
        $total = $bankObj->count();
        if ($type == '2') {
            $bankObj = $bankObj->limit($perPage)->offset($offset);
        }

        $res = $bankObj->get();
        //$result = $result->orderBy('id', 'DESC')->paginate($perPage);
        if ($type == '1') {
            $result = $res;
        } elseif ($type == '2') {
            $result['total'] = $total;
            $result['data']  = $res;
        }

        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deleteBankCard/:id 删除银行卡
     * @apiName DeleteBankCard
     * @apiGroup BankCards
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} id 银行卡id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "删除成功",
     *       "data": 1
     *   }
     */
    public function deleteBankCard(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $id     = $args['id'] ?? 0; //银行卡id
        if ($id > 0) {
            if (OfflinePay::where('card_id', '=', $id)->count() == 0) {
                //$bankObj = BankCards::where('id', '=', $id);
                $oldLevel = BankCards::where('id', '=', $id)->value('level_ids');
                $state = BankCards::where('id', '=', $id)->delete();
                if ($state) {
                    $result['status'] = 0;
                    $result['msg']    = '删除成功';
                    $result['data']   = $state;
                } else {
                    $result['msg'] = '删除失败!';
                }
            } else {
                $result['msg'] = '该银行卡下有支付记录，您不能删除';
            }
        }else{
            $result['msg'] = '缺少参数!';
        }
        if ($state) {
            $oldLevelArr = explode(',', $oldLevel);
            foreach ($oldLevelArr as $value) {
                Level::where('id', $value)->update(['status'=>'0']);
            }
            unset($value);
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /getPayBankByMemberLevel/:account 获取会员层级下的银行卡信息
     * @apiName GetPayBankByMemberLevel
     * @apiGroup BankCards
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiParam {String} account 会员账号
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 1,
     *       "msg": "无银行卡信息",
     *       "data": []
     *   }
     */
    public function getPayBankByMemberLevel(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '无银行卡信息', 'data' => []];
        $account = trim($args['account']);
        if ($account) {
            $member = Member::where('account', '=', $account)->first();
            if ($member) {
                $levelId = $member->level_id > 0 ? $member->level_id : 7489;
                if ($levelId > 0) {
                    $bankCard = BankCards::select(['id', 'bank_name', 'user_name', 'bank_number'])
                        ->whereRaw("`status` = '1' AND `type` = '2' AND FIND_IN_SET({$levelId}, `level_ids`)")
                        ->first();
                    if ($bankCard) {
                        $result['status'] = 0;
                        $result['msg']    = '';
                        $result['data']   = $bankCard;
                    }
                } else {
                    $result['msg'] = '会员无层级';
                }
            } else {
                $result['msg'] = '会员不存在';
            }
        }
        return $response->withJson($result);
    }

}
