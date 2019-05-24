<?php

namespace Weiming\Controllers;

use GuzzleHttp\Client;
use Illuminate\Pagination\Paginator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Utils;
use Weiming\Models\Company;
use Weiming\Models\PriQrcodePay;
use Weiming\Models\PrivateQrcode;

class PaySingleController extends BaseController
{
    /**
     * @api {post} /addSinglePay 添加个人二维码支付记录
     * @apiName AddPaySingle
     * @apiGroup PaySingle
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码id
     * @apiParam {Number} type 二维码类型 1:微信 2:支付宝
     * @apiParam {String} member 会员账号
     * @apiParam {Float}  money 金额
     * @apiParam {String} drawee 转款人(微信号/支付宝账号)
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "提交成功",
     *       "data": {
     *           "member": "ajs8888",
     *           "money": "500",
     *           "drawee": "Ran",
     *           "qrcode_id": "4",
     *           "updated_at": "2017-11-16 13:39:45",
     *           "created_at": "2017-11-16 13:39:45",
     *           "id": 1
     *       }
     *   }
     */
    public function addSinglePay(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '提交失败', 'data' => []];
        $postDatas = $request->getParsedBody();

        $id     = $postDatas['id'] ?? '';
        $member = $postDatas['member'] ?? '';
        $type   = $postDatas['type'] ?? 1;
        $money  = $postDatas['money'] ?? 0;
        $drawee = $postDatas['drawee'] ?? '';

        if ($id && $member && $drawee && in_array($type, ['1', '2', '3', '4']) && $money > 0) {
            $qrcode   = PrivateQrcode::where('id', '=', $id);
            $isQrcode = $qrcode->first();
            if ($isQrcode) {
                $priQrcodePay = PriQrcodePay::create([
                    'member'    => $member,
                    'money'     => $money,
                    'drawee'    => $drawee,
                    'type'      => $type,
                    'qrcode_id' => $id,
                ]);
                if ($priQrcodePay) {
                    //$qrcode->increment('count');//叠加收款次数
                    //$qrcode->increment('money');//叠加收款总额

                    $result['status'] = 0;
                    $result['msg']    = '提交成功';
                    $result['data']   = $priQrcodePay;
                } else {
                    $result['msg'] = '写入失败,请重试!';
                }
            } else {
                $result['msg'] = '奇怪,二维码走丢了!';
            }

        } else {
            $result['msg'] = '缺少参数';
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updatePaySingleState/:id 更新个人二维码支付状态
     * @apiName UpdatePaySingleState
     * @apiGroup PaySingle
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
    public function updatePaySingleState(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id        = $args['id'] ?? 0;
        $postDatas = $request->getParsedBody();
        $status    = $postDatas['status'] ?? 0;
        $msg       = $postDatas['msg'] ?? '';
        if ($id > 0) {
            $priCode = PriQrcodePay::where('id', '=', $id);
            $isPri   = $priCode->first();
            if ($isPri) {
                $data = [
                    'user_id' => $this->jwt->userInfo->id,
                    'msg'     => $msg,
                ];
                // 加锁防止连续点击入款按钮
                $redisLockey = 'qrcodePayRedisLock:' . $id;
                $isLock      = $this->redisLock->lock($redisLockey, 120);
                if ($isLock) {
                    // 充值
                    $company        = Company::where('no', '=', '00001')->first(); // 注：由于目前只有一个业务平台了，这里暂时写死为 00001 的业务平台
                    $isAutorecharge = $company->is_autorecharge;
                    $rechargeUrl    = $company->autorecharge_url;
                    if ($status == 1 && $isPri->status == 0 && $isAutorecharge == 1 && !empty($rechargeUrl)) {
                        // 只有 未处理 的记录，才执行入款操作
                        $money = $isPri->money;
                        if($isPri->type == 4)
                        {
                            $money = 1.01 * ($isPri->money);
                        }
                        $requestParams = [
                            'account'      => $isPri->member,
                            'fee'          => $money,
                            'orderNo'      => $id,
                            'rechargeTime' => date('Y-m-d H:i:s'),
                        ];

                        $requestParams['sign']   = Utils::generateSignature($requestParams);
                        $requestParams['act']    = 'useRecharge';
                        if($isPri->type == 1)
                        {
                            $requestParams['remark'] = '个人微信收款码';
                        }
                        elseif($isPri->type == 2)
                        {
                            $requestParams['remark'] = '个人支付宝收款码';
                        }
                        elseif($isPri->type == 3)
                        {
                            $requestParams['remark'] = '个人QQ收款码';
                        }
                        elseif($isPri->type == 4)
                        {
                            $requestParams['remark'] = '个人云闪付收款码';
                        }
                        $client                  = new Client();
                        //$rechargeRes             = $client->request('POST', $rechargeUrl, ['form_params' => $requestParams]);
                        /*# log #*/

                        try {
                            $rechargeRes             = $client->request('POST', $rechargeUrl, ['form_params' => $requestParams]);
                        } catch (ClientException $e) {
                            $getRequest  =$e->getRequest();
                            $getResponse =$e->getResponse();
                        }


                        $http_code=$rechargeRes->getStatusCode();
                        file_put_contents(__DIR__ . '/../../logs/lock_return_error' . date('Ymd') . '.txt', $http_code."====".$getRequest."===".$getResponse."====".$rechargeRes->getBody(). "\n", FILE_APPEND | LOCK_EX);



                        $resData_sign = $rechargeRes->getBody();
                        $resData_sign = json_decode($resData_sign, true);
                        $requestParams['url']=   $rechargeUrl;
                        $requestParams['code']=  $rechargeRes->getStatusCode();
                        $requestParams['array']=  $resData_sign;
                        $this->logger->addInfo('paySing-log:', $requestParams);
                        /*# log #*/
                        if ($rechargeRes->getStatusCode() == '200') {
                            $resData = $rechargeRes->getBody();
                            $resData = json_decode($resData, true);
                            $ret     = $resData['ret'];
                            $text    = $resData['text'];
                            // 备注原因
                            $data['msg'] = $text;
                            if ($ret == 1) {
                                // 成功将状态修改为 1 即 已入款
                                $status = 1;
                            } else {
                                // 未处理
                                $status = 0;
                            }
                        }
                    }
                    if (in_array($status, [0, 1, 2, 3] )) {
                        if($status == 3){
                            $status=0;
                        }
                        $data['status'] = strval($status);
                    }
                    $state = $priCode->update($data);
                    if ($state) {
                        // 更新二维码
                        if ($status == 1) {
                            $qrcode_id = $isPri->qrcode_id;
                            $qrcode    = PrivateQrcode::where('id', '=', $qrcode_id);
                            $isQrcode  = $qrcode->first();
                            if ($isQrcode) {
                                $qrcode->increment('count'); // 叠加收款次数
                                $qrcode->increment('money', $isPri->money); // 叠加收款总额
                            }
                        }
                        // Redis 解锁
                        $this->redisLock->unlock($redisLockey);
                        $result['status'] = 0;
                        $result['msg']    = '更新成功';
                        $result['data']   = $state;
                    }
                } else {
                    $result['msg'] = '请勿连续入款';
                }
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/paySingles 个人二维码支付列表
     * @apiName paySingles
     * @apiGroup PaySingle
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} type 支付类型,不提交默认为1 1:微信 2:支付宝
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数，默认为 20
     * @apiParam {String} key_word 关键字(二维码编号，会员账户)
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
     *               "money": 500,
     *               "drawee": "Ran",
     *               "qrcode_id": 4,
     *               "status": "1",
     *               "user_id": 1,
     *               "type": "1",
     *               "msg": "",
     *               "created_at": "2017-11-16 13:39:45",
     *               "updated_at": "2017-11-16 14:25:57",
     *               "user": {
     *                   "id": 1,
     *                   "username": "admin",
     *                   "realname": "admin",
     *                   "type": 1,
     *                   "permissions": "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43",
     *                   "company_ids": "1,2",
     *                   "lastlogin": "2017-11-16 08:52:06",
     *                   "ip": "127.0.0.1",
     *                   "status": 1,
     *                   "created_at": "2017-02-24 09:16:21",
     *                   "updated_at": "2017-11-16 08:52:06"
     *               },
     *               "qrcode": {
     *                   "id": 4,
     *                   "qrcode_name": "hello",
     *                   "url": "https://wm2028.oss-cn-hangzhou.aliyuncs.com/a949b001ef719edd1ec4619775d03a8d.png",
     *                   "money": 500,
     *                   "count": 1,
     *                   "type": "1",
     *                   "status": "0",
     *                   "msg": "",
     *                   "created_at": "2017-11-15 09:56:14",
     *                   "updated_at": "2017-11-16 14:25:57"
     *               }
     *           }
     *       ],
     *       "first_page_url": "/admin/paySingles?type=1&page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "/admin/paySingles?type=1&page=1",
     *       "next_page_url": null,
     *       "path": "/admin/paySingles?type=1",
     *       "per_page": 20,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *   }
     */
    public function paySingles(Request $request, Response $response, $args)
    {
        $getDatas   = $request->getQueryParams();
        $type       = $getDatas['type'] ?? '1';
        $key_word   = $getDatas['key_word'] ?? null;
        $startime   = $getDatas['startime'] ?? null;
        $endtime    = $getDatas['endtime'] ?? null;
        $page       = $getDatas['page'] ?? 1;
        $perPage    = $getDatas['perPage'] ?? 20;
        $down_excel = $getDatas['down_excel'] ?? '';

        Paginator::currentPathResolver(function () use ($type) {
            return "/admin/paySingles?type={$type}";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $payQrcodes = PriQrcodePay::with('user')->with('qrcode')->where('type', $type);

        // if ($startime) {
        //     $payQrcodes = $payQrcodes->whereRaw("`created_at` >= '" . $startime . "'");
        // }

        // if ($endtime) {
        //     $payQrcodes = $payQrcodes->whereRaw("`created_at` <= '" . $endtime . "'");
        // }

        if ($key_word) {
            $payQrcodes = $payQrcodes->whereRaw("(`member` LIKE '%{$key_word}%' or qrcode_id LIKE '%{$key_word}%')");
        }

        if ($startime) {
            $payQrcodes = $payQrcodes->whereRaw("`created_at` >= CONVERT_TZ('{$startime}', '-04:00', '+08:00')");
        }

        if ($endtime) {
            $payQrcodes = $payQrcodes->whereRaw("`created_at` <= CONVERT_TZ('{$endtime}', '-04:00', '+08:00')");
        }

        $payQrcodes = $payQrcodes->addSelect($this->db::raw("*, CONVERT_TZ(`created_at`, '+08:00', '-04:00') AS `created_at_edt`"));

        if (empty($down_excel)) {
            $priQrcodePay = $payQrcodes->orderBy('id', 'DESC')->paginate($perPage)->toArray();
            foreach($priQrcodePay['data'] as $k=>$v){
                if((time()-strtotime($v['created_at']))>24*3600){
                    $priQrcodePay['data'][$k]['ishftrue']=1;      //超过24小时
                }else{
                    $priQrcodePay['data'][$k]['ishftrue']=2;      //24小时内
                }
            }
            $priQrcodePay=(object)$priQrcodePay;
            return $response->withJson($priQrcodePay);
        } else {
            $result = $payQrcodes->orderBy('id', 'DESC')->get()->toArray();
            $header = ['会员账户', '二维码编号', '存款金额', '账户呢称', '支付时间(当地)', '支付时间(美东)', '状态'];
            $datas  = [];
            foreach ($result as $val) {
                $state = '未处理';
                if (isset($val['status'])) {
                    if ($val['status'] == '1') {
                        $state = '已入款';
                    } elseif ($val['status'] == '2') {
                        $state = '已忽略';
                    }
                }
                array_push($datas, [
                    0 => $val['member'],
                    1 => $val['qrcode_id'],
                    2 => $val['money'],
                    3 => $val['drawee'],
                    4 => $val['created_at'],
                    5 => $val['created_at_edt'],
                    6 => $state,
                ]);
            }
            if ($type == '1') {
                $filename = '微信个人码收款记录-' . date('Ymd') . '.xls';
            } elseif ($type == '2') {
                $filename = '支付宝个人码收款记录-' . date('Ymd') . '.xls';
            } elseif ($type == '3') {
                $filename = 'QQ个人码收款记录-' . date('Ymd') . '.xls';
            } elseif ($type == '4') {
                $filename = '云闪付收款记录-' . date('Ymd') . '.xls';
            } else {
                $filename = '';
            }
            $this->downExcel($header, $datas, $filename); //父类继承
        }
    }
}
