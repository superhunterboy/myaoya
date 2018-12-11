<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeByQrcodeJob;
use \Weiming\Libs\Utils;
use \Weiming\Models\Member;
use \Weiming\Models\PayQrcode;
use \Weiming\Models\Qrcode;

class PayQrcodeController extends BaseController
{
    /**
     * @api {post} /admin/updatePayQrcodeRemark/:id 智能微信备注
     * @apiName UpdatePayQrcodeRemark
     * @apiGroup PayQrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 入款记录id
     * @apiParam {String} remark 备注内容
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "备注成功",
     *       "data": []
     *   }
     */
    public function updatePayQrcodeRemark(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '备注失败', 'data' => []];
        $id        = $args['id'];
        $postDatas = $request->getParsedBody();
        $remark    = $postDatas['remark'] ?? null;
        if ($id > 0) {
            if (PayQrcode::where('id', '=', $id)->update([
                'result'     => $remark,
            ])) {
                $result['status'] = 0;
                $result['msg']    = '备注成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updatePayQrcodeStatus/:id 智能微信手动入款操作
     * @apiName UpdatePayQrcodeStatus
     * @apiGroup PayQrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 入款记录id
     * @apiParam {Number} status 状态：2 已入款、4 忽略
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "处理成功",
     *       "data": []
     *   }
     */
    public function updatePayQrcodeStatus(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '处理失败', 'data' => []];
        $id        = $args['id'];
        $postDatas = $request->getParsedBody();
        $status    = $postDatas['status'] ?? 0; // 2 已入款、4 忽略
        if ($id > 0 && $status > 0) {
            if (PayQrcode::where('id', '=', $id)->update([
                'status'     => $status,
                'rk_user_id' => $this->jwt->userInfo->id,
                'rk_user'    => $this->jwt->userInfo->username,
            ])) {
                $result['status'] = 0;
                $result['msg']    = '处理成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/getPayQrcodes?qrcodeId=:qrcodeId&account=:account&startime=:startime&endtime=:endtime&page=:page&perPage=:perPage 二维码支付列表
     * @apiName GetPayQrcodes
     * @apiGroup PayQrcode
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为 20
     * @apiParam {Number} qrcodeId 支付二维码id
     * @apiParam {Number} account 会员账号，二维码列表中 remark 字段的值
     * @apiParam {Number} startime 支付的起始时间
     * @apiParam {Number} endtime 支付的结束时间
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值   //暂未完善
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 3,                      // 支付记录id
     *               "sender": "silen",            // 微信昵称/微信号，取决于管控设备传过来的值
     *               "money": "101.00",            // 支付金额
     *               "createTime": 1507030677,     // 支付时间
     *               "code": "8kSzmArGTQNQBaY66BQCn9QYVn3Iskqy", // 流水号
     *               "recType": 1,                 // 收款类型：0 红包, 1 转账
     *               "remark": "silen",            // 会员账号
     *               "qrcode_id": 1,               // 二维码id
     *               "status": 0,                  // 入款状态，0 未处理、1 入款中、 2 已入款、3 入款失败
     *               "result": null,               // 入款结果信息
     *               "rk_user_id": 0,              // 入款人id
     *               "rk_user": "",                // 入款人
     *               "queue_job_id": null,         // 入款队列id
     *               "created_at": "2017-10-06 10:40:40",
     *               "updated_at": "2017-10-06 10:40:40"
     *           }
     *       ],
     *       "first_page_url": "/admin/getPayQrcodes?page=1",
     *       "from": 1,
     *       "last_page": 3,
     *       "last_page_url": "/admin/getPayQrcodes?page=3",
     *       "next_page_url": "/admin/getPayQrcodes?page=2",
     *       "path": "/admin/getPayQrcodes",
     *       "per_page": 1,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 3
     *   }
     */
    public function getPayQrcodes(Request $request, Response $response, $args)
    {
        $getDatas   = $request->getQueryParams();
        $page       = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage    = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;
        $qrcodeId   = $getDatas['qrcodeId'] ?? 0;
        $account    = $getDatas['account'] ?? ($getDatas['key_word'] ?? null);
        $startime   = $getDatas['startime'] ?? null;
        $endtime    = $getDatas['endtime'] ?? null;
        $down_excel = $getDatas['down_excel'] ?? '';

        Paginator::currentPathResolver(function () {
            return "/admin/getPayQrcodes";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $where = '1 = 1';

        $payQrcodes = PayQrcode::with('qrcode');

        if ($qrcodeId > 0) {
            $where .= " AND `id` = '{$qrcodeId}'";
        }

        if ($account) {
            $where .= " AND (`remark` LIKE '%{$account}%' OR `sender` LIKE '%{$account}%'";
            $qrcode = Qrcode::whereRaw("`wechat_id` LIKE '%{$account}%'")->first();
            if ($qrcode) {
                $where .= " OR `qrcode_id` = '{$qrcode->id}'";
            }
            $where .= ")";
        }

        if ($startime) {
            $where .= " AND `createTime` >= (UNIX_TIMESTAMP(CONVERT_TZ('{$startime}', '-04:00', '+08:00')) * 1000)";
        }

        if ($endtime) {
            $where .= " AND `createTime` <= (UNIX_TIMESTAMP(CONVERT_TZ('{$endtime}', '-04:00', '+08:00')) * 1000)";
        }

        $payQrcodes = $payQrcodes->whereRaw($where);

        $payQrcodes = $payQrcodes->addSelect($this->db::raw("*, CONVERT_TZ(FROM_UNIXTIME(`createTime`/1000), '+08:00', '-04:00') AS `createTime_edt`"));

        if (empty($down_excel)) {
            $payQrcodes = $payQrcodes->orderBy('id', 'DESC')->paginate($perPage);
            return $response->withJson($payQrcodes);
        } else {
            // $result = $payQrcodes->orderBy('id', 'DESC')->get()->toArray();
            // $header = ['流水号', '支付微信号', '支付金额', '会员名称', '收款微信号', '状态', '结果', '支付时间', '创建时间', '入款人'];

            return $response->withJson(['code' => 1, 'msg' => '群控还未完善']);
        }
    }

    public function addPayQrcode(Request $request, Response $response, $args)
    {
        $result   = 'fail';
        $getDatas = $request->getQueryParams();
        if ($request->isPost()) {
            $getDatas = $request->getParsedBody();
        }
        $this->logger->addInfo("Wechat QRCode Callback Datas:", $getDatas);
        $service    = $getDatas['service'] ?? null;
        $qrcodeId   = $args['id'];
        $money      = $getDatas['money'] ?? 0;
        $sender     = $getDatas['sender'] ?? '';
        $createTime = $getDatas['createTime'] ?? null;
        $recCode    = $getDatas['rec_code'] ?? ($getDatas['code'] ?? '');
        $remark     = $getDatas['remark'] ?? '';
        $recType    = $getDatas['rec_type'] ?? ($getDatas['recType'] ?? 0);
        // 备注乱码问题
        if (strpos($remark, "\x00") !== false) {
            $remark = substr($remark, 0, strpos($remark, "\x00"));
        }
        // money应为float
        $money = floatval(str_replace('￥', '', $money));

        // // 给测试者显示东西,方便他自己测试
        // if(strstr((string)$qrcodeId,'2017')){
        //     $log_name = '/usr/share/nginx/html/wmpay/logs/system-'.$qrcodeId.'.log';
        //     $myfile = fopen($log_name, "r") or die("Unable to open file!");
        //     echo fread($myfile,filesize($log_name));
        //     fclose($myfile);
        //     return;
        // }

        // recType = 红包：0 / 转账：1   TODO: 删除recType的判断
        if (empty($service) && $qrcodeId && $money > 0 && $sender && $createTime && $recCode) {
            //
            // 这个表不太完整，要增加status,result等
            // 表里面qrcodeId字段是int[10] 要改成字符串
            $tmpArr               = [];
            $tmpArr['qrcode_id']  = $qrcodeId;
            $tmpArr['money']      = $money;
            $tmpArr['sender']     = $sender;
            $tmpArr['createTime'] = $createTime;
            $tmpArr['code']       = $recCode;
            $tmpArr['remark']     = trim($remark);
            $tmpArr['recType']    = $recType;
            // 有没有存在的二维码 修改 id => wechat_id
            $qrcode      = Qrcode::where('wechat_id', '=', $qrcodeId);
            $qrcodeCount = $qrcode->count();
            // 有没有存在的流水号
            $payQrcode      = PayQrcode::where('code', '=', $recCode);
            $payQrcodeCount = $payQrcode->count();
            // Test
            $this->logger->addInfo("reformed: ", $tmpArr);

            // 有二维码、没有流水号就创建
            if ($qrcodeCount == 1 && $payQrcodeCount == 0) {
                // 二维码的微信号修正为ID，qrcode_id 为关联字段
                $qrcodeObj           = $qrcode->first();
                $wechatQrcodeId      = $qrcodeObj->id;
                $tmpArr['qrcode_id'] = $wechatQrcodeId;
                $payQrcode           = PayQrcode::create($tmpArr);
                if ($payQrcode) {
                    // 扫码次数递增
                    $qrcode->increment('count');
                    // 收款总额递增
                    $qrcode->increment('money', $tmpArr['money']);
                    // 每天收款金额，0点后要清0
                    $qrcode->increment('day_money', $tmpArr['money']);
                    // 判断会员是否存在
                    if (Member::where('account', '=', trim($remark))->count() == 1) {
                        $rechargeTime  = substr($createTime, 0, 10);
                        $threeHoursAgo = strtotime('-3 hour');
                        if ($rechargeTime <= $threeHoursAgo) {
                            $payQrcode->result = '迟到3小时的充值';
                            $payQrcode->save();
                        } else {
                            // 解决今天收到昨天的入款记录，疑似重复入款
                            $isDouble = PayQrcode::where('id', '!=', $payQrcode->id)
                                ->where('money', '=', $money)
                                ->where('createTime', '=', $createTime)
                                ->where('recType', '=', $recType)
                                ->where('remark', '=', trim($remark))
                                ->where('qrcode_id', '=', $wechatQrcodeId)
                                ->count();
                            if ($isDouble == 0) {
                                // 自动充值任务
                                $company        = $payQrcode->qrcode->company;
                                $isAutoRecharge = $company->is_autorecharge;
                                $rechargeUrl    = $company->autorecharge_url;
                                if ($isAutoRecharge == 1 && $rechargeUrl) {
                                    $requestParams = [
                                        'account'      => trim($remark),
                                        'fee'          => $money,
                                        'orderNo'      => $recCode,
                                        'rechargeTime' => date('Y-m-d H:i:s', $rechargeTime),
                                    ];
                                    $requestParams['sign'] = Utils::generateSignature($requestParams);
                                    $token                 = Resque::enqueue(
                                        'qrcode',
                                        AutoRechargeByQrcodeJob::class,
                                        [
                                            'rechargeUrl'   => $rechargeUrl,
                                            'requestParams' => $requestParams,
                                        ],
                                        true
                                    );
                                    if ($token) {
                                        // 这里没有测试
                                        $payQrcode->queue_job_id = $token;
                                        $payQrcode->status       = 1;
                                        $payQrcode->result       = '正在自动入款';
                                        $payQrcode->save();
                                    }
                                }
                            } else {
                                $payQrcode->result = '疑似重复入款';
                                $payQrcode->save();
                            }
                        }
                    } else {
                        $payQrcode->result = '账号未识别，不能自动入款';
                        $payQrcode->save();
                    }
                    $result = 'success';
                }
            }
        }
        $response->getBody()->write($result);
        return $response;
    }
}
