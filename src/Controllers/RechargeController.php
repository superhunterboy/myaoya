<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Payments\Aifu;
use \Weiming\Libs\Payments\Bingo;
use \Weiming\Libs\Payments\Duobao;
use \Weiming\Libs\Payments\JinhaizheNew;
use \Weiming\Libs\Payments\Kailiantong;
use \Weiming\Libs\Payments\Nongfu;
use \Weiming\Libs\Payments\Shangma;
use \Weiming\Libs\Payments\Shunxin;
use \Weiming\Libs\Payments\Tianfubao;
use \Weiming\Libs\Payments\Tianji;
use \Weiming\Libs\Payments\Xifu;
use \Weiming\Libs\Payments\Xunjie;
use \Weiming\Libs\Payments\YafuNew;
use \Weiming\Libs\Payments\Zesheng;
use \Weiming\Libs\Payments\Gaiya;
use \Weiming\Libs\Utils;
use \Weiming\Models\Platform;
use \Weiming\Models\Recharge;

class RechargeController extends BaseController
{
    public function recharge(Request $request, Response $response, $args)
    {
        $postDatas      = $request->getParsedBody();
        $type           = isset($postDatas['type']) ? intval($postDatas['type']) : 0;
        $companyId      = isset($postDatas['companyId']) ? intval($postDatas['companyId']) : 0;
        $platformId     = isset($postDatas['platformId']) ? intval($postDatas['platformId']) : 0;
        $bank           = isset($postDatas['bank']) ? strval($postDatas['bank']) : '';
        $amount         = isset($postDatas['amount']) ? floatval($postDatas['amount']) : 0;
        $rechargeLinkId = isset($postDatas['rechargeLinkId']) ? intval($postDatas['rechargeLinkId']) : 0; // 0为后台充值、大于0为前台充值
        if ($type > 0 && $companyId > 0 && $platformId > 0 && $bank && $amount > 0 && $rechargeLinkId >= 0) {
            $orderId  = Utils::getOrderId(date('YmdHis'));
            $recharge = Recharge::create([
                'company_id'       => $companyId,
                'platform_id'      => $platformId,
                'pay_out_type'     => $type,
                'pay_code'         => $bank,
                'amount'           => $amount,
                'order_no'         => $orderId,
                'recharge_link_id' => $rechargeLinkId,
            ]);
            if ($recharge) {
                $platform = Platform::where('id', '=', $platformId)->first();
                if ($platform) {
                    $res  = '';
                    $conf = [
                        'parterNo'    => $platform->no,
                        'parterKey'   => $platform->key,
                        'callbackUrl' => $platform->callback_url,
                        'notifyUrl'   => $platform->notify_url,
                    ];
                    if ($type == 1) {
                        $res = Tianfubao::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 2) {
                        $res = YafuNew::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 3) {
                        $res = JinhaizheNew::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 4) {
                        $res = Zesheng::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 6) {
                        $res = Kailiantong::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 8) {
                        $res = Shangma::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 9) {
                        $res = Xifu::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 10) {
                        $parterKey         = json_decode($conf['parterKey'], true);
                        $conf['parterKey'] = $parterKey['md5Key'];
                        $res               = Aifu::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 11) {
                        $res = Nongfu::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 12) {
                        $res = Shunxin::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 13) {
                        $res = Tianji::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 14) {
                        $res = Xunjie::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 15) {
                        $res = Duobao::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 16) {
                        $res = Bingo::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 20) {
                        $res = Gaiya::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 21) {
                        $res = Qingying::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    } elseif ($type == 22) {
                        $res = Jiyun::getInstance($conf)->signature($bank, $amount, $orderId)->payment();
                    }
                    if (preg_match("/^(http|https):\/\/.*$/", $res)) {
                        return $response->withStatus(302)->withHeader('Location', $res);
                    } else {
                        $response->getBody()->write($res);
                    }
                }
            }
        }
        return $response;
    }

    /**
     * @api {get} /admin/getRecharges?page=:page&perPage=:perPage&platformId=:platformId&orderNo=:orderNo&platformOrderNo=:platformOrderNo 出款平台充值记录列表
     * @apiName GetRecharges
     * @apiGroup Recharge
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为 20
     * @apiParam {Number} platformId 出款平台 id
     * @apiParam {String} startime 开始时间
     * @apiParam {String} endtime 结束时间
     * @apiParam {String} orderNo 单号
     * @apiParam {String} platformOrderNo 流水号
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 184,
     *               "company_id": 1,
     *               "platform_id": 27,
     *               "pay_out_type": 13,
     *               "pay_code": "01000000",
     *               "amount": "100.00",
     *               "order_no": "201803291155542085554",
     *               "platform_order_no": "",
     *               "status": 1,
     *               "remark": "",
     *               "recharge_link_id": 1, // 大于0 recharge_link 是有值的，即表示前台充值，0 为 后台充值
     *               "created_at": "2018-03-29 11:55:54",
     *               "updated_at": "2018-03-29 11:55:54",
     *               "recharge_link": {
     *                   "id": 1,
     *                   "platform_id": 27,
     *                   "token": "7b9e49b79200cb8069dff056cd6aba2b",
     *                   "remark": "张三",
     *                   "created_at": "2018-03-29 11:13:41",
     *                   "updated_at": "2018-03-29 11:13:43"
     *               }
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
    public function getRecharges(Request $request, Response $response, $args)
    {
        $getDatas        = $request->getQueryParams();
        $page            = $getDatas['page'] ?? 1;
        $perPage         = $getDatas['perPage'] ?? 20;
        $platformId      = $getDatas['platformId'] ?? 0;
        $orderNo         = $getDatas['orderNo'] ?? '';
        $platformOrderNo = $getDatas['platformOrderNo'] ?? '';
        $startime        = $getDatas['startime'] ?? '';
        $endtime         = $getDatas['endtime'] ?? '';
        $down_excel      = $getDatas['down_excel'] ?? '';

        Paginator::currentPathResolver(function () {
            return "/admin/getRecharges";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        if ($platformId > 0) {
            $recharges = Recharge::with('rechargeLink')->where('platform_id', '=', $platformId);
        } else {
            $recharges = Recharge::with('rechargeLink')->where('id', '>', 0);
        }
        if ($orderNo) {
            $recharges = $recharges->where('order_no', '=', $orderNo);
        }
        if ($platformOrderNo) {
            $recharges = $recharges->where('platform_order_no', '=', $platformOrderNo);
        }
        if ($startime) {
            $recharges = $recharges->where('created_at', '>', $startime);
        }
        if ($endtime) {
            $recharges = $recharges->where('created_at', '<', $endtime);
        }

        if (empty($down_excel)) {
            $recharges = $recharges->orderBy('id', 'DESC')->paginate($perPage);
            return $response->withJson($recharges);
        } else {
            $result = $recharges->orderBy('id', 'DESC')->get()->toArray();
            $header = ['订单号', '流水号', '充值时间', '充值金额', '充值状态'];
            $datas  = [];
            foreach ($result as $val) {
                $state = '未知';
                if (isset($val['status'])) {
                    if ($val['status'] == '1') {
                        $state = '失败';
                    } elseif ($val['status'] == '0') {
                        $state = '成功';
                    }
                }
                array_push($datas, [
                    0 => $val['order_no'] ?? '',
                    1 => $val['platform_order_no'] ?? '',
                    2 => $val['created_at'] ?? '',
                    3 => $val['amount'] ?? '',
                    4 => $state,
                ]);
            }

            $filename = '出款平台充值记录-' . date('Ymd') . '.xls';
            $this->downExcel($header, $datas, $filename); //父类继承
        }
    }
}
