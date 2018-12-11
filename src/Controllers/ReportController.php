<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \PHPExcel;
use \PHPExcel_Cell_DataType;
use \PHPExcel_IOFactory;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Crawler;
use \Weiming\Libs\Utils;
use \Weiming\Models\Report;
use \Weiming\Models\ReportArtificialDeposit;
use \Weiming\Models\ReportL1;
use \Weiming\Models\ReportPayCompany;
use \Weiming\Models\ReportPayOnline;

class ReportController extends BaseController
{
    private function flushReportCache()
    {
        $reportIdArr   = [];
        $reportL1IdArr = [];
        // 美东时间
        $starTime = Utils::myGMDate('Y-m-d', strtotime("-3 day"), -4);
        $endTime  = Utils::myGMDate('Y-m-d', time(), -4);
        // 报表类型
        $reports = Report::select(['id', 'time', 'flag', 'total'])->whereRaw("`time` >= '{$starTime}' AND `time` <= '{$endTime}'")->get();
        foreach ($reports as $report) {
            // 收集报表类型ID
            array_push($reportIdArr, $report->id);
            // 报表类型数据是否有变
            $cacheKey = 'isUpdateReport:' . md5($report->time . $report->flag . $report->total);
            if ($this->redis->exists($cacheKey)) {
                continue;
            }
            $this->redis->set($cacheKey, json_encode([
                'id'    => $report->id,
                'time'  => $report->time,
                'flag'  => $report->flag,
                'total' => $report->total,
            ]));
            $this->redis->expireat($cacheKey, strtotime("+3 day"));
        }
        // 报表类型下的分类
        if (empty($reportIdArr)) {
            return;
        }
        $reportL1s = ReportL1::select(['id', 'report_id', 'tag', 'total'])->whereIn('report_id', $reportIdArr)->get();
        foreach ($reportL1s as $reportL1) {
            // 收集报表分类ID
            array_push($reportL1IdArr, $reportL1->id);
            // 报表分类数据是否有变
            $cacheKey = 'isUpdateReportL1:' . md5($reportL1->report_id . $reportL1->tag . $reportL1->total);
            if ($this->redis->exists($cacheKey)) {
                continue;
            }
            $this->redis->set($cacheKey, json_encode([
                'id'        => $reportL1->id,
                'report_id' => $reportL1->report_id,
                'total'     => $reportL1->total,
                'tag'       => $reportL1->tag,
            ]));
            $this->redis->expireat($cacheKey, strtotime("+3 day"));
        }
        // 报表分类下的入款数据
        if (empty($reportL1IdArr)) {
            return;
        }
        $reportL1Ids = implode(',', $reportL1IdArr);
        $sql = "(SELECT rpc.`order_no`, CONCAT('payCompany') AS `type` FROM (SELECT `id` FROM `report_l1` WHERE `id` IN ({$reportL1Ids})) AS rl, (SELECT `report_l1_id`, `order_no` FROM `report_pay_company` WHERE `report_l1_id` IN ({$reportL1Ids})) AS rpc WHERE rl.`id` = rpc.`report_l1_id`) UNION (SELECT rpo.`order_no`, CONCAT('payOnline') AS `type` FROM (SELECT `id` FROM `report_l1` WHERE `id` IN ({$reportL1Ids})) AS rl, (SELECT `report_l1_id`, `order_no` FROM `report_pay_online` WHERE `report_l1_id` IN ({$reportL1Ids})) AS rpo WHERE rl.`id` = rpo.`report_l1_id`) UNION (SELECT rad.`order_no`, CONCAT('artificialDeposit') AS `type` FROM (SELECT `id` FROM `report_l1` WHERE `id` IN ({$reportL1Ids})) AS rl, (SELECT `report_l1_id`, `order_no` FROM `report_artificial_deposit` WHERE `report_l1_id` IN ({$reportL1Ids})) AS rad WHERE rl.`id` = rad.`report_l1_id`)";
        $reportL2s = $this->db::select($sql);
        foreach ($reportL2s as $reportL2) {
            // 报表分类数据是否有变
            $cacheKey = 'report:' . $reportL2->type . '#' . $reportL2->order_no;
            if ($this->redis->exists($cacheKey)) {
                continue;
            }
            $this->redis->set($cacheKey, $reportL2->order_no);
            $this->redis->expireat($cacheKey, strtotime("+3 day"));
        }
    }

    public function addReportDatas(Request $request, Response $response, $args)
    {
        // 刷新缓存
        $this->flushReportCache();
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('Report Data.', $postDatas);
        if (isset($postDatas['jsonData']) && $postDatas['jsonData']) {
            $tmpArr = json_decode($postDatas['jsonData'], true);
            if ($tmpArr) {
                $reportId   = [];
                $reportL1Id = [];
                $report     = $tmpArr['report'] ?? null;
                $reportL1   = $tmpArr['report_l1'] ?? null;
                $reportL2   = $tmpArr['report_l2'] ?? null;
                if ($report) {
                    // 报表类型
                    foreach ($report as $key => $val) {
                        $flag     = $val['flag']; // 报表类型: pay_company、artificial_Deposit、pay_online
                        $time     = $val['time']; // 报表日期
                        $total    = $val['total']; // 可根据这个字段判断数据是否有变化
                        $cacheKey = 'isUpdateReport:' . md5($time . $flag . $total);
                        if ($this->redis->exists($cacheKey)) {
                            // 数据无变化
                            $tmpObj          = json_decode($this->redis->get($cacheKey));
                            $reportId[$flag] = $tmpObj->id;
                            // 结束本次循环
                            continue;
                        }
                        // 数据有变化，要么更新，要么插入新数据
                        $reportObj = Report::updateOrCreate(['flag' => $flag, 'time' => $time], $val);
                        if ($reportObj) {
                            $reportId[$flag] = $reportObj->id;
                        }
                    }
                    if ($reportL1) {
                        // 报表分类
                        foreach ($reportL1 as $key => $val) {
                            $rid = $reportId[$key];
                            foreach ($val as $key1 => $val1) {
                                $tag               = $val1['tag']; // 报表类型下的分类ID
                                $total             = $val1['total']; // 可根据这个字段判断数据是否有变化
                                $val1['report_id'] = $rid;
                                $cacheKey          = 'isUpdateReportL1:' . md5($rid . $tag . $total);
                                if ($this->redis->exists($cacheKey)) {
                                    // 数据无变化
                                    $tmpObj           = json_decode($this->redis->get($cacheKey));
                                    $reportL1Id[$tag] = $tmpObj->id;
                                    // 结束本次循环
                                    continue;
                                }
                                // 数据有变化，要么更新，要么插入新数据
                                $reportL1Obj = ReportL1::updateOrCreate(['tag' => $tag, 'report_id' => $rid], $val1);
                                if ($reportL1Obj) {
                                    $reportL1Id[$tag] = $reportL1Obj->id;
                                }
                            }
                        }
                        if ($reportL2) {
                            foreach ($reportL2 as $key => $val) {
                                $tmp   = explode(',', $key); // artificial_Deposit,1010,15  含义：报表类型,报表类型下的分类ID,页码
                                $type  = $tmp[0]; // 报表类型
                                $type2 = $tmp[1]; // 报表类型下的分类ID
                                $page  = $tmp[2]; // 页码
                                $rlid  = $reportL1Id[$type2];
                                if ($type == 'pay_company') {
                                    foreach ($val as $val1) {
                                        $discountReg = "/\s*([\d\.]+)\s*\(([\d\.]+)\)\s*/";
                                        $datetimeReg = "/.*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}).*/";
                                        if (preg_match($discountReg, $val1[5][2][1], $discount) &&
                                            preg_match($datetimeReg, $val1[8][0][1], $memberDatetime) &&
                                            preg_match($datetimeReg, $val1[8][1][1], $systemDatetime) &&
                                            preg_match($datetimeReg, $val1[8][2][1], $operationDatetime)
                                        ) {
                                            $orderNo  = $val1[1];
                                            $cacheKey = 'report:payCompany#' . $orderNo;
                                            // 是否被缓存，存在缓存表示已经存在数据库中
                                            if ($this->redis->exists($cacheKey)) {
                                                continue;
                                            }
                                            $reportPayCompany = ReportPayCompany::updateOrCreate([
                                                'order_no' => $orderNo,
                                            ], [
                                                'report_l1_id'       => $rlid,
                                                'level'              => $val1[0],
                                                'order_no'           => $orderNo,
                                                'shareholder'        => $val1[2],
                                                'account'            => $val1[3],
                                                'account_bank'       => $val1[4][0][1],
                                                'depositor'          => $val1[4][2][1],
                                                'way'                => $val1[4][4][1],
                                                'amount'             => str_replace(',', '', $val1[5][0][1]),
                                                'discount'           => str_replace(',', '', $val1[5][1][1]),
                                                'other_discount1'    => str_replace(',', '', $discount[1]),
                                                'other_discount2'    => str_replace(',', '', $discount[2]),
                                                'total_amount'       => str_replace(',', '', $val1[5][4][1]),
                                                'company_bank'       => $val1[6][0][1],
                                                'company_bank_user'  => $val1[6][2][1],
                                                'operator'           => $val1[7],
                                                'member_datetime'    => $memberDatetime[1],
                                                'system_datetime'    => $systemDatetime[1],
                                                'operation_datetime' => $operationDatetime[1],
                                            ]);
                                            if ($reportPayCompany && $reportPayCompany->wasRecentlyCreated) {
                                                // 第一次被创建，缓存单号，缓存3天
                                                $this->redis->set($cacheKey, $orderNo);
                                                $this->redis->expireat($cacheKey, strtotime("+3 day"));
                                            }
                                        }
                                    }
                                } elseif ($type == 'pay_online') {
                                    foreach ($val as $val1) {
                                        $orderNo  = $val1[0];
                                        $cacheKey = 'report:payOnline#' . $orderNo;
                                        // 是否被缓存，存在缓存表示已经存在数据库中
                                        if ($this->redis->exists($cacheKey)) {
                                            continue;
                                        }
                                        $reportPayOnline = ReportPayOnline::updateOrCreate([
                                            'order_no' => $orderNo,
                                        ], [
                                            'report_l1_id' => $rlid,
                                            'order_no'     => $orderNo,
                                            'account'      => $val1[1],
                                            'currency'     => $val1[2],
                                            'level'        => $val1[3],
                                            'time'         => $val1[4],
                                            'amount'       => str_replace(',', '', $val1[5]),
                                        ]);
                                        if ($reportPayOnline && $reportPayOnline->wasRecentlyCreated) {
                                            // 第一次被创建，缓存单号，缓存3天
                                            $this->redis->set($cacheKey, $orderNo);
                                            $this->redis->expireat($cacheKey, strtotime("+3 day"));
                                        }
                                    }
                                } elseif ($type == 'artificial_Deposit') {
                                    foreach ($val as $val1) {
                                        if (preg_match("/(\d+)/", $val1[3], $matches)) {
                                            $orderNo  = $matches[1];
                                            $cacheKey = 'report:artificialDeposit#' . $orderNo;
                                            // 是否被缓存，存在缓存表示已经存在数据库中
                                            if ($this->redis->exists($cacheKey)) {
                                                continue;
                                            }
                                            $reportArtificialDeposit = ReportArtificialDeposit::updateOrCreate([
                                                'order_no' => $orderNo,
                                            ], [
                                                'report_l1_id' => $rlid,
                                                'order_no'     => $orderNo,
                                                'account'      => $val1[0],
                                                'type'         => $val1[1],
                                                'amount'       => str_replace(',', '', $val1[2]),
                                                'remark'       => $val1[4],
                                                'time'         => $val1[5],
                                            ]);
                                            if ($reportArtificialDeposit && $reportArtificialDeposit->wasRecentlyCreated) {
                                                // 第一次被创建，缓存单号，缓存3天
                                                $this->redis->set($cacheKey, $orderNo);
                                                $this->redis->expireat($cacheKey, strtotime("+3 day"));
                                            }
                                        } elseif (preg_match("/(\s*)/", $val1[3], $matches)) {
                                            // 人工存入取消出款 无单号 解决办法
                                            $orderNo = $matches[1];
                                            if (empty($orderNo)) {
                                                $orderNo = md5($val1[0] . $val1[2] . $val1[5]);
                                            }
                                            $cacheKey = 'report:artificialDeposit#' . $orderNo;
                                            // 是否被缓存，存在缓存表示已经存在数据库中
                                            if ($this->redis->exists($cacheKey)) {
                                                continue;
                                            }
                                            $reportArtificialDeposit = ReportArtificialDeposit::updateOrCreate([
                                                'order_no' => $orderNo,
                                            ], [
                                                'report_l1_id' => $rlid,
                                                'order_no'     => $orderNo,
                                                'account'      => $val1[0],
                                                'type'         => $val1[1],
                                                'amount'       => str_replace(',', '', $val1[2]),
                                                'remark'       => $val1[4],
                                                'time'         => $val1[5],
                                            ]);
                                            if ($reportArtificialDeposit && $reportArtificialDeposit->wasRecentlyCreated) {
                                                // 第一次被创建，缓存单号，缓存3天
                                                $this->redis->set($cacheKey, $orderNo);
                                                $this->redis->expireat($cacheKey, strtotime("+3 day"));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $response->getBody()->write("Ok, Report data has been submitted to the payment system.\n");
        }
        return $response;
    }

    /**
     * @api {get} /admin/getPayCompanyReport?page=:page&perPage=:perPage&items=:items&keyword=:keyword&startime=:startime&endtime=:endtime 公司入款列表
     * @apiName GetPayCompanyReport
     * @apiGroup Report
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为 30
     * @apiParam {String} items 搜索项
     * @apiParam {String} keyword 单号/账号
     * @apiParam {String} startime 起始时间，默认为当前美东时间的 00:00:00
     * @apiParam {String} endtime 结束时间，默认为当前美东时间的 23:59:59
     * @apiParam {Number} isExport 是否导出，传入此参数就可导出数据，默认为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 3316,
     *               "report_l1_id": 106,
     *               "level": "未分層",                             // 层级
     *               "order_no": "2017101965953306",                // 单号
     *               "shareholder": "ajs8888",                      // 股东
     *               "account": "asd88680",                         // 会员账号
     *               "account_bank": "招商",                        // 存款人银行
     *               "depositor": "赵红远",                         // 存款人
     *               "way": "網轉",                                 // 方式
     *               "amount": "6000.00",                           // 存入金额
     *               "discount": "60.00",                           // 存款优惠
     *               "other_discount1": "0.00",                     // 其他优惠
     *               "other_discount2": "0.00",                     // 其他优惠
     *               "total_amount": "6060.00",                     // 存入总金额
     *               "company_bank": "华融湘江",                    // 公司银行
     *               "company_bank_user": "华融湘江银行-张湘南",    // 公司银行卡主姓名
     *               "operator": "2ashley",                         // 操作人
     *               "member_datetime": "2017-10-19 18:14:00",      // 会员填写当地时间
     *               "system_datetime": "2017-10-19 06:15:55",      // 系统提交时间(美东)
     *               "operation_datetime": "2017-10-19 06:18:35",   // 操作时间(美东)
     *               "created_at": "2017-10-19 18:21:45",
     *               "updated_at": "2017-10-19 18:21:45"
     *           }
     *       ],
     *       "first_page_url": "/admin/getPayCompanyReport?page=1",
     *       "from": 1,
     *       "last_page": 4,
     *       "last_page_url": "/admin/getPayCompanyReport?page=4",
     *       "next_page_url": "/admin/getPayCompanyReport?page=2",
     *       "path": "/admin/getPayCompanyReport",
     *       "per_page": "1",
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 4,
     *       "total_amount": "14000.00",                             // 总计
     *       "current_total_amount": 6000                            // 小计
     *   }
     */
    public function getPayCompanyReport(Request $request, Response $response, $args)
    {
        $result   = [];
        $getDatas = $request->getQueryParams();
        $items    = $getDatas['items'] ?? '';
        $keyword  = $getDatas['keyword'] ?? '';
        $startime = $getDatas['startime'] ?? Utils::myGMDate('Y-m-d 00:00:00', '', -4);
        $endtime  = $getDatas['endtime'] ?? Utils::myGMDate('Y-m-d 23:59:59', '', -4);
        $isExport = $getDatas['isExport'] ?? 0;
        $page     = $getDatas['page'] ?? 1;
        $perPage  = $getDatas['perPage'] ?? 30;

        Paginator::currentPathResolver(function () {
            return "/admin/getPayCompanyReport";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        if ($startime) {
            $result = ReportPayCompany::where('operation_datetime', '>=', $startime);
        }

        if ($endtime) {
            $result = $result->where('operation_datetime', '<=', $endtime);
        }

        if ($items) {
            $items  = explode(',', $items);
            $result = $result->whereIn('report_l1_id', $items);
        }

        if ($keyword) {
            $result = $result->whereRaw("`account` = '{$keyword}' OR `order_no` = '{$keyword}'");
        }

        $result = $result->orderBy('system_datetime', 'DESC');

        if (empty($isExport)) {
            $totalAmount        = $result->sum('amount');
            $result             = $result->paginate($perPage);
            $currentTotalAmount = $result->sum('amount');
        } else {
            $result = $result->get();
        }

        $result = $result->toArray();

        if (empty($isExport)) {
            $result['total_amount']         = $totalAmount;
            $result['current_total_amount'] = round($currentTotalAmount, 2);
            return $response->withJson($result);
        } else {
            $header = [
                '层级',
                '订单号',
                '大股东',
                '会员账号',
                '会员银行',
                '存款人',
                '方式',
                '存入金额',
                '存款优惠',
                '其他优惠',
                '存入总金额',
                '公司银行',
                '公司银行卡主姓名',
                '操作者',
                '会员填写(当地)',
                '系统提交(美东)',
                '操作时间(美东)',
            ];
            $datas = [];
            foreach ($result as $key => $val) {
                array_push($datas, [
                    0  => $val['level'],
                    1  => $val['order_no'],
                    2  => $val['shareholder'],
                    3  => $val['account'],
                    4  => $val['account_bank'],
                    5  => $val['depositor'],
                    6  => $val['way'],
                    7  => $val['amount'],
                    8  => $val['discount'],
                    9  => $val['other_discount1'] . '(' . $val['other_discount2'] . ')',
                    10 => $val['total_amount'],
                    11 => $val['company_bank'],
                    12 => $val['company_bank_user'],
                    13 => $val['operator'],
                    14 => $val['member_datetime'],
                    15 => $val['system_datetime'],
                    16 => $val['operation_datetime'],
                ]);
            }
            $this->generateExcelFile($header, $datas);
        }
    }

    /**
     * @api {get} /admin/getPayOnlineReport?page=:page&perPage=:perPage&items=:items&keyword=:keyword&startime=:startime&endtime=:endtime 线上支付列表
     * @apiName GetPayOnlineReport
     * @apiGroup Report
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为 30
     * @apiParam {String} items 搜索项
     * @apiParam {String} keyword 单号/账号
     * @apiParam {String} startime 起始时间，默认为当前美东时间的 00:00:00
     * @apiParam {String} endtime 结束时间，默认为当前美东时间的 23:59:59
     * @apiParam {Number} isExport 是否导出，传入此参数就可导出数据，默认为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 5973,
     *               "report_l1_id": 87,
     *               "order_no": "201710200647508336",         // 单号
     *               "account": "1092976086",                  // 会员账号
     *               "currency": "人民币",                     // 币别
     *               "level": "★总存款【5万】",                // 会员支付层级
     *               "time": "2017-10-19 21:07:51",            // 时间(美东)
     *               "amount": "600.90",                       // 存入金额
     *               "created_at": "2017-10-20 09:10:19",
     *               "updated_at": "2017-10-20 09:10:19",
     *               "report_l1": {
     *                   "id": 87,
     *                   "report_id": 8,
     *                   "text": "金銀寶-金银宝",              // 公司线上入款账号
     *                   "total": "200,721.28",
     *                   "tag": 88237,
     *                   "currency": "RMB",
     *                   "total_user": 317,
     *                   "total_amount": "200721.28",
     *                   "created_at": "2017-10-19 12:16:51",
     *                   "updated_at": "2017-10-20 09:09:59"
     *               }
     *           }
     *       ],
     *       "first_page_url": "/admin/getPayOnlineReport?page=1",
     *       "from": 1,
     *       "last_page": 2050,
     *       "last_page_url": "/admin/getPayOnlineReport?page=2050",
     *       "next_page_url": "/admin/getPayOnlineReport?page=2",
     *       "path": "/admin/getPayOnlineReport",
     *       "per_page": "1",
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 2050,
     *       "total_amount": "709227.39",                    // 总计
     *       "current_total_amount": 600.9                   // 小计
     *   }
     */
    public function getPayOnlineReport(Request $request, Response $response, $args)
    {
        $result   = [];
        $getDatas = $request->getQueryParams();
        $items    = $getDatas['items'] ?? '';
        $keyword  = $getDatas['keyword'] ?? '';
        $startime = $getDatas['startime'] ?? Utils::myGMDate('Y-m-d 00:00:00', '', -4);
        $endtime  = $getDatas['endtime'] ?? Utils::myGMDate('Y-m-d 23:59:59', '', -4);
        $isExport = $getDatas['isExport'] ?? 0;
        $page     = $getDatas['page'] ?? 1;
        $perPage  = $getDatas['perPage'] ?? 30;

        Paginator::currentPathResolver(function () {
            return "/admin/getPayOnlineReport";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        if ($startime) {
            $result = ReportPayOnline::where('time', '>=', $startime);
        }

        if ($endtime) {
            $result = $result->where('time', '<=', $endtime);
        }

        if ($items) {
            $items  = explode(',', $items);
            $result = $result->whereIn('report_l1_id', $items);
        }

        if ($keyword) {
            $result = $result->whereRaw("`account` = '{$keyword}' OR `order_no` = '{$keyword}'");
        }

        $result = $result->with('reportL1');

        $result = $result->orderBy('time', 'DESC');

        if (empty($isExport)) {
            $totalAmount        = $result->sum('amount');
            $result             = $result->paginate($perPage);
            $currentTotalAmount = $result->sum('amount');
        } else {
            $result = $result->get();
        }

        $result = $result->toArray();

        if (empty($isExport)) {
            $result['total_amount']         = $totalAmount;
            $result['current_total_amount'] = round($currentTotalAmount, 2);
            return $response->withJson($result);
        } else {
            $header = ['单号', '账号', '币别', '会员支付层级', '时间(美东)', '存入金额', '商号'];
            $datas  = [];
            foreach ($result as $key => $val) {
                array_push($datas, [
                    0 => $val['order_no'],
                    1 => $val['account'],
                    2 => $val['currency'],
                    3 => $val['level'],
                    4 => $val['time'],
                    5 => $val['amount'],
                    6 => $val['report_l1']['text'],
                ]);
            }
            $this->generateExcelFile($header, $datas);
        }
    }

    /**
     * @api {get} /admin/getArtificialDepositReport?page=:page&perPage=:perPage&items=:items&keyword=:keyword&startime=:startime&endtime=:endtime 人工存入列表
     * @apiName GetArtificialDepositReport
     * @apiGroup Report
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为1
     * @apiParam {Number} perPage 每页数据总数，不提交默认为 30
     * @apiParam {String} items 搜索项
     * @apiParam {String} keyword 单号/账号
     * @apiParam {String} startime 起始时间，默认为当前美东时间的 00:00:00
     * @apiParam {String} endtime 结束时间，默认为当前美东时间的 23:59:59
     * @apiParam {Number} isExport 是否导出，传入此参数就可导出数据，默认为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 1103,
     *               "report_l1_id": 88,
     *               "account": "x127226",                    // 会员账号
     *               "type": "人工存入",                      // 项目
     *               "amount": "200.00",                      // 金额
     *               "order_no": "2017101919183199643",       // 单号
     *               "remark": "審核人：3york(微信收款)",     // 备注
     *               "time": "2017-10-19 07:18:06",           // 时间(美东)
     *               "created_at": "2017-10-19 19:20:52",
     *               "updated_at": "2017-10-19 19:20:52"
     *           }
     *       ],
     *       "first_page_url": "/admin/getArtificialDepositReport?page=1",
     *       "from": 1,
     *       "last_page": 187,
     *       "last_page_url": "/admin/getArtificialDepositReport?page=187",
     *       "next_page_url": "/admin/getArtificialDepositReport?page=2",
     *       "path": "/admin/getArtificialDepositReport",
     *       "per_page": "1",
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 187,
     *       "total_amount": "136188.30",                     // 总计
     *       "current_total_amount": 200                      // 小计
     *   }
     */
    public function getArtificialDepositReport(Request $request, Response $response, $args)
    {
        $result   = [];
        $getDatas = $request->getQueryParams();
        $items    = $getDatas['items'] ?? '';
        $keyword  = $getDatas['keyword'] ?? '';
        $startime = $getDatas['startime'] ?? Utils::myGMDate('Y-m-d 00:00:00', '', -4);
        $endtime  = $getDatas['endtime'] ?? Utils::myGMDate('Y-m-d 23:59:59', '', -4);
        $isExport = $getDatas['isExport'] ?? 0;
        $page     = $getDatas['page'] ?? 1;
        $perPage  = $getDatas['perPage'] ?? 30;

        Paginator::currentPathResolver(function () {
            return "/admin/getArtificialDepositReport";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        if ($startime) {
            $result = ReportArtificialDeposit::where('time', '>=', $startime);
        }

        if ($endtime) {
            $result = $result->where('time', '<=', $endtime);
        }

        if ($items) {
            $items  = explode(',', $items);
            $result = $result->whereIn('report_l1_id', $items);
        }

        if ($keyword) {
            $result = $result->whereRaw("`account` = '{$keyword}' OR `order_no` = '{$keyword}' OR `remark` LIKE '%{$keyword}%'");
        }

        $result = $result->orderBy('time', 'DESC');

        if (empty($isExport)) {
            $totalAmount        = $result->sum('amount');
            $result             = $result->paginate($perPage);
            $currentTotalAmount = $result->sum('amount');
        } else {
            $result = $result->get();
        }

        $result = $result->toArray();

        if (empty($isExport)) {
            $result['total_amount']         = $totalAmount;
            $result['current_total_amount'] = round($currentTotalAmount, 2);
            return $response->withJson($result);
        } else {
            $header = ['账号', '项目', '金额', '单号', '备注', '时间(美东)'];
            $datas  = [];
            foreach ($result as $key => $val) {
                array_push($datas, [
                    0 => $val['account'],
                    1 => $val['type'],
                    2 => $val['amount'],
                    3 => $val['order_no'],
                    4 => $val['remark'],
                    5 => $val['time'],
                ]);
            }
            $this->generateExcelFile($header, $datas);
        }
    }

    /**
     * @api {get} /admin/getReportItems?type=:type 获取搜索项目
     * @apiName GetReportItems
     * @apiGroup Report
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} type 类型，1 为 公司入款、2 为 线上支付、 3 为 人工存入
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "5": "农业银行--汪建华",
     *       "6": "恒星付--飞军腾达",
     *       "12": "华融湘江银行-刘功成",
     *       "42": "中信银行--杨文佳",
     *       "48": "支付宝-燕子",
     *       "55": "微信扫码-炫云国际",
     *       "105": "微信扫码--永乐商贸",
     *       "112": "华融湘江银行--杨文佳",
     *       "115": "建设银行-刘功成",
     *       "1,40,114": "农业银行--颜从亮",
     *       "2,41,79": "建设银行--刘树松",
     *       "3,111": "中信银行--郑浩东",
     *       "4,44": "恒星付--恒辉瑞",
     *       "7,46,108": "中信银行--胡丹",
     *       "8,113": "支付宝-佳佳",
     *       "9,47,110": "支付宝-洛洛",
     *       "10,49,81": "农业银行-匡海超",
     *       "11,50": "工商银行-阳宇浩",
     *       "13,51,106": "华融湘江银行-张湘南",
     *       "14,82": "支付宝-林林",
     *       "15,52,83": "支付宝-云云",
     *       "16,53": "支付宝-辰辰",
     *       "17,54": "支付宝-凤凤",
     *       "43,80": "工商银行--秦园林",
     *       "45,107": "恒星付--德平达"
     *   }
     */
    public function getReportItems(Request $request, Response $response, $args)
    {
        $result    = [];
        $reportL1s = [];
        $flag      = 1;
        $getDatas  = $request->getQueryParams();
        $type      = $getDatas['type'] ?? null;
        if ($type == 1) {
            $flag = 'pay_company';
        } elseif ($type == 2) {
            $flag = 'pay_online';
        } elseif ($type == 3) {
            $flag = 'artificial_Deposit';
        }
        $reports = Report::where('flag', '=', $flag)->get();
        foreach ($reports as $report) {
            foreach ($report->reportL1s as $reportL1) {
                $reportL1s[$reportL1->tag][$reportL1->id] = $reportL1->text;
            }
        }
        if ($reportL1s) {
            foreach ($reportL1s as $reportL1) {
                $key          = implode(',', array_keys($reportL1));
                $val          = array_values($reportL1)[0];
                $result[$key] = $val;
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/pullReportDatas?date=:date 拉取报表数据
     * @apiName PullReportDatas
     * @apiGroup Report
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Date} date 拉取哪天的数据，如：2017-10-20（美东）
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "提交成功，拉取数据需要一些时间",
     *       "data": []
     *   }
     */
    public function pullReportDatas(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '提交失败', 'data' => []];
        $getDatas = $request->getQueryParams();
        $date     = $getDatas['date'] ?? '';
        if ($date) {
            $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
            $ret        = Crawler::getInstance()->updatePayOutData(
                $crawlerUrl,
                [
                    'act'  => 'pullReportDatas',
                    'date' => $date,
                ]
            );
            if (strpos($ret, 'true') !== false) {
                $result['status'] = 0;
                $result['msg']    = '提交成功，拉取数据需要一些时间';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 生成Excel表格
     * @param  array  $header 表头
     * @param  array  $datas  表数据
     */
    private function generateExcelFile($header = [], $datas = [])
    {
        $filename = date("YmdHis") . '.xls';
        $excel    = new PHPExcel();
        // 表头
        $letters = range('A', 'Z');
        foreach ($header as $key => $field) {
            $letter = array_shift($letters);
            $excel->getActiveSheet()->setCellValue($letter . '1', $field);
        }
        // 数据
        foreach ($datas as $key => $data) {
            $i       = $key + 2;
            $letters = range('A', 'Z');
            foreach ($data as $val) {
                $letter = array_shift($letters);
                $excel->getActiveSheet()->setCellValueExplicit($letter . $i, $val, PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        // 标签标题
        $excel->getActiveSheet()->setTitle(substr($filename, 0, -4));
        // 请求头
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        // 输出内容
        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $objWriter->save('php://output');
    }
}
