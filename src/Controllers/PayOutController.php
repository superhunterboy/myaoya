<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Resque_Job_Status;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoPayOutCheckJob;
use \Weiming\Jobs\AutoPayOutJob;
use \Weiming\Jobs\ManualPayOutCheckJob;
use \Weiming\Libs\AgencyPayments\Aifu;
use \Weiming\Libs\AgencyPayments\Bingo;
use \Weiming\Libs\AgencyPayments\Chuanhua;
use \Weiming\Libs\AgencyPayments\Duobao;
use \Weiming\Libs\AgencyPayments\Jiayoutong;
use \Weiming\Libs\AgencyPayments\Jinhaizhe;
use \Weiming\Libs\AgencyPayments\KaiLianTong;
use \Weiming\Libs\AgencyPayments\Nongfu;
use \Weiming\Libs\AgencyPayments\Shangma;
use \Weiming\Libs\AgencyPayments\Shunxin;
use \Weiming\Libs\AgencyPayments\Tianfubao;
use \Weiming\Libs\AgencyPayments\Xifu;
use \Weiming\Libs\AgencyPayments\Xinxinju;
use \Weiming\Libs\AgencyPayments\Xunjie;
use \Weiming\Libs\AgencyPayments\Yafu;
use \Weiming\Libs\AgencyPayments\Yibao;
use \Weiming\Libs\AgencyPayments\Zesheng;
use \Weiming\Libs\AgencyPayments\Zhongdian;
use \Weiming\Libs\AgencyPayments\Gaiya;
use \Weiming\Libs\AgencyPayments\Jiyun;
use \Weiming\Libs\AgencyPayments\Qingying;
use \Weiming\Libs\Crawler;
use \Weiming\Libs\Utils;
use \Weiming\Models\Member;
use \Weiming\Models\PayOut;
use \Weiming\Models\PayOutLimit;
use \Weiming\Models\Platform;
use \Weiming\Models\Withdrawal;

class PayOutController extends BaseController
{
    /**
     * 是否有符合额度限制范围内的出款平台，有就返回出款平台数据
     * @param  integer $amount 出款金额
     * @return boolean false 为没有符合条件的，否则有符合条件的
     */
    private function amountIsLimit($amount = 0)
    {
        $platforms = Platform::where('enabled', '=', 1)->get();
        if ($platforms->count() > 0) {
            foreach ($platforms as $platform) {
                if ($platform->start_amount_limit <= $amount && $amount <= $platform->end_amount_limit) {
                    return $platform;
                }
            }
            return false;
        }
        return false;
    }

    // public function testPayOut(Request $request, Response $response, $args)
    // {
    //     var_dump($this->amountIsLimit(1000) == false);
    //     exit();
    // }

    /**
     * @api {post} /addPayOut 添加出款记录，仅限于BBIN平台(爬虫接口)
     * @apiName AddPayOut
     * @apiGroup PayOut
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *       "jsonData": "[[\"\\u6fb3\\u4e9e\\u570b\\u969b\",\"\\u2605\\u2605\\u603b\\u5b58\\u6b3e\\u301050\\u4e07\\u3011\",\"ajs8888\",\"djs8888\",\"mczone\",\"40000\",\"0\",\"0\",{\"id\":\"203032842\",\"member\":{\"account_name\":\"\\u9a6c\\u8d85-1\",\"bank\":\"\\u5de5\\u5546\\u94f6\\u884c\",\"account\":\"6215593202011545368\",\"province\":\"\\u6e56\\u5317\",\"city\":\"\\u5de5\\u5546\\u94f6\\u884c\\u5e38\\u9752\\u82b1\\u56ed\\u652f\\u884c\",\"tel\":\"15827407019\",\"detail_modified\":false,\"note\":\"\\u5df2\\u53d1\\u77ed\\u4fe1\"},\"value\":\"40000\"},\"\",\"\\u5426\",\"2017-09-15 07:48:14\",\"\",\"\",\"\",\"\"],[\"\\u6fb3\\u4e9e\\u570b\\u969b\",\"\\u672a\\u5206\\u5c64\",\"ajs8888\",\"dffy555\",\"zpy808\",\"200\",\"90\",\"1.99\",{\"id\":\"203032518\",\"member\":{\"account_name\":\"\\u8d75\\u57f9\\u7389\",\"bank\":\"\\u519c\\u4e1a\\u94f6\\u884c\",\"account\":\"6228480339398697674\",\"province\":\"\\u6d59\\u6c5f\",\"city\":\"\\u6e29\\u5dde\",\"tel\":\"15158040264\",\"detail_modified\":false,\"note\":\"\"},\"value\":\"108.01\"},\"\\u9996\\u6b21\\u51fa\\u6b3e\",\"\\u662f\",\"2017-09-15 07:46:53\",\"\",\"\",\"\\u672a\\u5b8c\\u6210\\u6253\\u7801\\u91cf\",\"\"],[\"\\u6fb3\\u4e9e\\u570b\\u969b\",\"\\u672a\\u5206\\u5c64\",\"ajs8888\",\"djs8888\",\"li060426\",\"200\",\"0\",\"0\",{\"id\":\"203026012\",\"member\":{\"account_name\":\"\\u674e\\u96c4\",\"bank\":\"\\u5efa\\u8bbe\\u94f6\\u884c\",\"account\":\"621700120068367679\",\"province\":\"\\u4e0a\\u6d77\",\"city\":\"\\u4e0a\\u6d77\",\"tel\":\"18964581999\",\"detail_modified\":false,\"note\":\"\\u9996\\u51fa\\u81f4\\u7535\\u6838\\u5bf9\\u4e00\\u4e0b\\u4fee\\u6539\\u4fe1\\u606f 2017-09-15\\u5ba2\\u670d\\u90e8ivy\"},\"value\":\"200\"},\"\",\"\\u5426\",\"2017-09-15 07:22:28\",\"\",\"\",\"\\u65e0\\u6cd5\\u652f\\u4ed8\",\"2017-09-15 07:33:52\"]]"
     *   }
     *
     * @apiSuccessExample {string} Success-Response:
     *   HTTP/1.1 200 OK
     *   Ok, The data has been submitted to the payment system.
     */
    public function add(Request $request, Response $response, $args)
    {
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('Pay Out Data.', $postDatas);
        if (isset($postDatas['jsonData']) && $postDatas['jsonData']) {
            $tmpArr = json_decode($postDatas['jsonData'], true);
            /**
             *   Array
             *   (
             *       [0] => 206839557                                          // 出款记录ID
             *       [1] => 澳亞國際                                           // 站别
             *       [2] => ▲总存款【1千】                                     // 层级
             *       [3] => ajs8888                                            // 大股东账号
             *       [4] => drita888                                           // 代理商账号
             *       [5] => ww1985                                             // 会员账号
             *       [6] => 2710                                               // 提出额度
             *       [7] => 0                                                  // 手续费
             *       [8] => 0                                                  // 优惠金额
             *       [9] => Array
             *           (
             *               [id] => 206839557                                 // 出款记录ID
             *               [member] => Array
             *                           (
             *                               [account_name] => 陈琪锋          // 户名
             *                               [bank] => 农业银行                // 银行名称
             *                               [account] => 6228480372192617515  // 银行账号
             *                               [province] => 浙江省              // 省份
             *                               [city] => 绍兴市                  // 城市
             *                               [tel] => 18368520213              // 手机
             *                               [detail_modified] =>              // 真实姓名有异动
             *                               [note] => 已核实 Queenie          // 备注
             *                           )
             *
             *               [value] => 2710                                   // 出款资讯
             *           )
             *
             *       [10] => 首次出款                                          // 出款状况
             *       [11] => 否                                                // 优惠扣除
             *       [12] => 0                                                 // 支付平台手续费
             *       [13] => 2710                                              // 实际出款金额
             *       [14] => 请选择                                            // 出款商号
             *       [15] => 2017-09-27 14:26:47                               // 出款日期
             *       [16] => 确定                                              // 已出款，锁定/解锁、确定、取消、拒绝
             *       [17] => 2ruben                                            // 操作者
             *       [18] => 无人接听                                          // 备注
             *       [19] => 2017-09-27 14:40:42                               // 最后异动时间
             *   )
             */
            $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
            foreach ($tmpArr as $k => $v) {
                $orderNo        = Utils::getOrderId(date('Y-m-d H:i:s'));
                $amount         = $v[6]; // 提出额度
                $cash_info      = $v[9]['value']; // 出款资讯
                $pay_out_status = $v[10]; // 出款状况
                $wid            = $v[9]['id']; // 出款记录ID
                $account        = $v[5]; // 会员账号
                $realname       = $v[9]['member']['account_name']; // 户名
                $bankCard       = str_replace(' ', '', Utils::des_ecb_decrypt($v[9]['member']['account'], $this->settings['key'])); // 银行账号
                //$mobile         = Utils::des_ecb_decrypt($v[9]['member']['tel'], $this->settings['key']); // 手机
                $mobile   = ''; // 手机
                $bankName = $v[9]['member']['bank']; // 银行名称
                $province = $v[9]['member']['province']; // 省份
                $city     = $v[9]['member']['city']; // 城市
                $branch   = $v[9]['member']['branch'];
                // 爬虫提交过来的数据只有：未处理(锁定、确定、取消、拒绝)、已锁定(xxx已锁定)
                $status = 0;
                if (strpos($v[16], '已锁定') !== false) {
                    $status = 4;
                }
                $payOutIsExist = PayOut::where('wid', '=', $wid)->count();
                // 判断是否有符合条件的出款平台
                $platform = $this->amountIsLimit($amount);
                // 1、已锁定 2、有优惠 3、首次出款 4、出款金额不在额度限制范围内 5、已存在的出款单，这5种情况不走自动出款通道
                if ($status != 0 || $amount != $cash_info || $pay_out_status == '首次出款' || $platform == false || $payOutIsExist > 0) {
                    continue;
                }
                // 干掉商码付以下出款银行的情况，财务部要求干掉，干吧！！！
                if ($platform->pay_out_type == 8 && (strpos($bankName, '中行') !== false || strpos($bankName, '中国银行') !== false || strpos($bankName, '邮政') !== false || strpos($bankName, '华夏') !== false || strpos($bankName, '浦发') !== false || strpos($bankName, '浦东发展') !== false)) {
                    continue;
                }
                // 限制会员是否超过出款次数
                if (!$this->isPossiblePayOut($account)) {
                    // 更新BBIN备注
                    $result = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateRemark',
                        'id'      => $wid,
                        'content' => '自动出款次数受限',
                    ]);
                    $this->logger->addInfo('Pay out count limit.', ['id' => $wid, 'result' => $result]);
                    continue;
                }
                $payOut = PayOut::updateOrCreate(
                    [
                        'wid' => $wid,
                    ],
                    [
                        'wid'                => $wid,
                        'account'            => $account,
                        'realname'           => $realname,
                        'mobile'             => $mobile,
                        'bank_card'          => $bankCard,
                        'bank_name'          => $bankName,
                        'amount'             => $amount,
                        'service_charge'     => $v[7], // 手续费
                        'discount'           => $v[8], // 优惠金额
                        'cash_info'          => $cash_info,
                        'pay_out_status'     => $pay_out_status,
                        'order_no'           => $orderNo,
                        'discount_deduction' => $v[11], // 优惠扣除
                        'crawl_attach'       => json_encode($v),
                        'pay_out_time'       => $v[15], // 出款日期
                        'status'             => $status,
                        'remark'             => $v[18], // 备注
                        'pay_out_lastime'    => $v[19], // 最后异动时间
                        'platform_id'        => $platform->id,
                        'platform_type'      => $platform->pay_out_type,
                    ]
                );
                // 只处理第一次被创建的出款记录
                // if ($payOut && $payOut->wasRecentlyCreated && in_array($account, ['ceshi4', 'ceshi1'])) {
                if ($payOut && $payOut->wasRecentlyCreated) {
                    // 1、调用接口更新状态为已锁定 2、添加到出款队列中
                    $result = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateStatus',
                        'id'      => $wid,
                        'status'  => 4, // 锁定
                        'account' => $account,
                    ]);
                    $this->logger->addInfo('Crawler modify pay out status.', ['id' => $wid, 'result' => $result]);
                    if (strpos($result, 'true') !== false) {
                        // 加入队列
                        $tranAmt     = $amount * 100; // 金额单位：分
                        $realnameArr = explode('-', $realname);
                        $token       = Resque::enqueue(
                            'crawler',
                            AutoPayOutJob::class,
                            [
                                'orderNo'  => $orderNo,
                                'tranAmt'  => $tranAmt,
                                'acctName' => $realnameArr[0],
                                'acctId'   => $bankCard,
                                'mobile'   => $mobile,
                                'bankName' => $bankName,
                                'province' => $province,
                                'city'     => $city,
                                'branch'   => $branch,
                            ],
                            true
                        );
                        if ($token) {
                            $payOut->job_id = $token;
                            $payOut->status = 4;
                            $payOut->save();
                        }
                        // 更新备注
                        $result = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                            'act'     => 'updateRemark',
                            'id'      => $wid,
                            'content' => '自动出款中...',
                        ]);
                    }
                }
            }
            $response->getBody()->write("Ok, Payment out data has been submitted to the payment system.\n");
        }
        return $response;
    }

    /**
     * @api {post} /addGPKPayOut 添加出款记录，仅限于GPK平台(爬虫接口)
     * @apiName AddGPKPayOut
     * @apiGroup PayOut
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *       "jsonData": ""
     *   }
     *
     * @apiSuccessExample {string} Success-Response:
     *   HTTP/1.1 200 OK
     *   Ok, The data has been submitted to the payment system.
     */
    public function addGPK(Request $request, Response $response, $args)
    {
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('Pay Out Data.', $postDatas);
        if (isset($postDatas['jsonData']) && $postDatas['jsonData']) {
            $tmpArr     = json_decode($postDatas['jsonData'], true);
            $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
            foreach ($tmpArr as $k => $v) {
                $orderNo            = Utils::getOrderId(date('Y-m-d H:i:s'));
                $amount             = $v['amount']; // 提出额度
                $cash_info          = $v['cash_info']; // 出款资讯
                $discount           = $v['discount']; // 行政费
                $service_charge     = $v['service_charge']; // 手续费
                $discount_deduction = $v['discount_deduction']; // 优惠扣除
                $pay_out_status     = $v['pay_out_status']; // 首次出款，GPK没有这个字段
                $wid                = $v['wid']; // 出款记录ID
                $account            = $v['account']; // 会员账号
                $realname           = $v['realname']; // 户名
                $bankCard           = Utils::des_ecb_decrypt($v['bank_card'], $this->settings['key']); // 银行账号
                //$mobile             = Utils::des_ecb_decrypt($v['mobile'], $this->settings['key']); // 手机
                $mobile   = ''; // 手机
                $bankName = $v['bank_name']; // 银行名称
                $province = $v['province']; // 省份
                $city     = $v['city']; // 城市
                $levelId  = $v['memberLevelId']; // 层级ID
                $branch   = $v['branch'] ?? ''; // GPK没有这个字段
                // 爬虫提交过来的数据只有：未处理，GPK平台不分锁定和未锁定，只有确定、取消、拒绝这三种状态，为了方便操作，与bbin保持一致
                $status = 0;
                // 中文姓名重名区分字符除去
                if (preg_match('/[\x{4e00}-\x{9fa5}]+[·\x{4e00}-\x{9fa5}]*/u', $realname, $matches)) {
                    $realname = $matches[0];
                } else {
                    // 更新GPK备注
                    $result = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateRemark',
                        'id'      => $wid,
                        'content' => '姓名有误',
                    ]);
                    $this->logger->addInfo('Pay out deny.', ['id' => $wid, 'result' => $result]);
                    continue;
                }
                // 订单是否已经存在
                $payOutIsExist = PayOut::where('wid', '=', $wid)->count();
                // 判断是否有符合条件的出款平台
                $platform = $this->amountIsLimit($amount);
                // 1、不是未处理的 2、有手续费、行政费、优惠扣除 3、出款金额不在额度限制范围内 4、已存在的出款单，这5种情况不走自动出款通道
                if ($status != 0 || $discount > 0 || $service_charge > 0 || $discount_deduction > 0 || $platform == false || $payOutIsExist > 0) {
                    continue;
                }
                // 干掉商码付以下出款银行的情况，财务部要求干掉，干吧！！！
                if ($platform->pay_out_type == 8 && (strpos($bankName, '中行') !== false || strpos($bankName, '中国银行') !== false || strpos($bankName, '邮政') !== false || strpos($bankName, '华夏') !== false || strpos($bankName, '浦发') !== false || strpos($bankName, '浦东发展') !== false)) {
                    continue;
                }
                // 限制会员是否超过出款次数
                if (!$this->isPossiblePayOutGPK($levelId)) {
                    // 更新GPK备注
                    $result = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateRemark',
                        'id'      => $wid,
                        'content' => '自动出款次数受限',
                    ]);
                    $this->logger->addInfo('Pay out count limit.', ['id' => $wid, 'result' => $result]);
                    continue;
                }
                $payOut = PayOut::updateOrCreate(
                    [
                        'wid' => $wid,
                    ],
                    [
                        'wid'                => $wid,
                        'account'            => $account,
                        'realname'           => $realname,
                        'mobile'             => $mobile,
                        'bank_card'          => $bankCard,
                        'bank_name'          => $bankName,
                        'amount'             => $amount,
                        'service_charge'     => $service_charge, // 手续费
                        'discount'           => $discount, // 行政
                        'cash_info'          => $cash_info,
                        'pay_out_status'     => $pay_out_status,
                        'order_no'           => $orderNo,
                        'discount_deduction' => $discount_deduction, // 优惠扣除
                        'crawl_attach'       => $v['crawl_attach'],
                        'pay_out_time'       => $v['pay_out_time'], // 出款日期
                        'status'             => $status,
                        'remark'             => $v['remark'], // 备注
                        'pay_out_lastime'    => $v['pay_out_lastime'], // 最后异动时间
                        'platform_id'        => $platform->id,
                        'platform_type'      => $platform->pay_out_type,
                    ]
                );
                // 只处理第一次被创建的出款记录
                // if ($payOut && $payOut->wasRecentlyCreated && in_array($account, ['a383912060'])) {
                if ($payOut && $payOut->wasRecentlyCreated) {
                    // 加入队列
                    $tranAmt = $amount * 100; // 金额单位：分
                    $token   = Resque::enqueue(
                        'crawler',
                        AutoPayOutJob::class,
                        [
                            'orderNo'  => $orderNo,
                            'tranAmt'  => $tranAmt,
                            'acctName' => $realname,
                            'acctId'   => $bankCard,
                            'mobile'   => $mobile,
                            'bankName' => $bankName,
                            'province' => $province,
                            'city'     => $city,
                            'branch'   => $branch,
                        ],
                        true
                    );
                    if ($token) {
                        $payOut->job_id = $token;
                        $payOut->status = 4; // GPK平台是假锁定，解锁也是假解锁
                        $payOut->save();
                    }
                    // 更新备注
                    $result = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateRemark',
                        'id'      => $wid,
                        'content' => '自动出款中...',
                    ]);
                }
            }
            $response->getBody()->write("Ok, Payment out data has been submitted to the payment system.\n");
        }
        return $response;
    }

    /**
     * @api {get} /admin/queryPayOut?page=:page&perPage=:perPage&startime=:startime&endtime=:endtime&account=:account&status=:status 出款记录列表
     * @apiName QueryPayOut
     * @apiGroup PayOut
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} page 当前页
     * @apiParam {Number} perPage 每页数据条数
     * @apiParam {String} serial_number 流水号
     * @apiParam {String} startime 开始时间
     * @apiParam {String} endtime 结束时间
     * @apiParam {String} account 会员账号
     * @apiParam {Number} status 出款状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
     * @apiParam {Number} down_excel 导出execl 导出传1  不导出 不传值
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 2267,
     *               "wid": 204174620,                           // BBIN 出款记录id
     *               "account": "ceshi1",                        // 会员账号
     *               "realname": "黄泽界",                       // 会员姓名
     *               "mobile": "12345678967",                    // 会员手机
     *               "bank_card": "6214837578067963",            // 会员银行卡
     *               "bank_name": "招商银行",                    // 银行名称
     *               "amount": "50.00",                          // 出款金额，单位：元
     *               "discount": "0.00",                         // 优惠
     *               "service_charge": "0.00",                   // 手续费
     *               "cash_info": "50.00",                       // 出款资讯
     *               "discount_deduction": "否",                 // 优惠扣除
     *               "pay_out_status": "",                       // 出款状况
     *               "platform_id": 1,                           // 出款平台id
     *               "platform_type": 1,                           // 出款平台类型，1 天付宝、2 雅付、3 金海哲
     *               "order_no": "201709191409112980911",        // 出款单号
     *               "platform_order_no": "2017091918260274",    // 流水号
     *               "platform_status": 3,                       // 出款平台状态，1 处理成功、2 处理中、3 处理失败、4 已退汇
     *               "status": 4,                                // 状态，0未处理 1确定 2取消 3拒绝 4已锁定
     *               "user_id": 1,                               // 操作人id
     *               "user": "admin",                            // 操作人
     *               "remark": "",                               // 备注内容
     *               "platform_attach": "{\"acct_id\":\"6214****7963\",\"acct_name\":\"\\u9ec4\\u6cfd\\u754c\",\"bank_name\":\"\\u62db\\u5546\\u94f6\\u884c\",\"business_type\":\"20101\",\"cur_type\":\"1\",\"memo\":\"\\u63d0\\u73b0\",\"serialno_desc\":\"\\u5546\\u6237\\u53ef\\u7528\\u4f59\\u989d\\u4e0d\\u8db3\",\"serialno_state\":\"3\",\"sp_serialno\":\"201709191409112980911\",\"spid\":\"1800218655\",\"tfb_rsptime\":\"20170919165433\",\"tfb_serialno\":\"2017091918260274\",\"tran_amt\":\"5000\",\"sign\":\"1263060d488cea252c6b2b72f0a6eadf\"}",  // 出款平台返回消息
     *               "crawl_attach": "[\"\\u6fb3\\u4e9e\\u570b\\u969b\",\"\\u6d4b\\u8bd5\\u5c42\\u7ea7\",\"ajs8888\",\"djs8888\",\"ceshi1\",\"50\",\"0\",\"0\",{\"id\":\"204174620\",\"member\":{\"account_name\":\"\\u9ec4\\u6cfd\\u754c\",\"bank\":\"\\u62db\\u5546\\u94f6\\u884c\",\"account\":\"6214837578067963\",\"province\":\"\\u4f60\\u597d\",\"city\":\"\\u6211\\u597d\",\"tel\":\"12345678967\",\"detail_modified\":false,\"note\":\"\"},\"value\":\"50\"},\"\",\"\\u5426\",\"2017-09-19 02:09:08\",\"\\u672a\\u5904\\u7406\",\"\",\"\",\"\"]",  // 爬虫爬取的原始数据
     *               "pay_out_time": "2017-09-19 02:09:08",      // BBIN后台出款时间
     *               "pay_out_lastime": "0000-00-00 00:00:00",   // BBIN后台异动时间
     *               "job_id": "8471351c458d444da57755c84bdf162a",  // 队列任务id
     *               "company_id": 1,                             // 业务平台id
     *               "created_at": "2017-09-19 14:09:11",        // 创建时间
     *               "updated_at": "2017-09-19 16:54:33"         // 更新时间
     *           }
     *       ],
     *       "first_page_url": "/admin/queryPayOut?page=1",
     *       "from": 1,
     *       "last_page": 2374,
     *       "last_page_url": "/admin/queryPayOut?page=2374",
     *       "next_page_url": "/admin/queryPayOut?page=2",
     *       "path": "/admin/queryPayOut",
     *       "per_page": 1,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 2374
     *   }
     */
    public function query(Request $request, Response $response, $args)
    {
        $result        = [];
        $getDatas      = $request->getQueryParams();
        $startime      = $getDatas['startime'] ?? '';
        $endtime       = $getDatas['endtime'] ?? '';
        $account       = $getDatas['account'] ?? '';
        $serial_number = $getDatas['serial_number'] ?? '';
        $status        = $getDatas['status'] ?? null;
        $page          = $getDatas['page'] ?? 1;
        $perPage       = $getDatas['perPage'] ?? 10;
        $down_excel    = $getDatas['down_excel'] ?? '';
        // 数据权限
        $companyIds      = $this->jwt->userInfo->company_ids;
        $currentUserType = $this->jwt->userInfo->type;
        Paginator::currentPathResolver(function () {
            return "/admin/queryPayOut";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        // 管理员看全部业务平台
        if ($currentUserType == 1) {
            $result = PayOut::whereRaw("`id` > 0");
        } else {
            $result = PayOut::whereRaw("`company_id` IN ({$companyIds})");
        }

        if ($startime) {
            $result = $result->whereRaw("`pay_out_time` >= '{$startime}'");
        }

        if ($endtime) {
            $result = $result->whereRaw("`pay_out_time` <= '{$endtime}'");
        }

        if ($account) {
            $result = $result->whereRaw("`account` LIKE '%{$account}%'");
        }

        if ($serial_number) {
            $result = $result->where('platform_order_no', $serial_number);
        }

        if (strlen($status) > 0) {
            $result = $result->whereRaw("`platform_status` = {$status}");
        }

        if (empty($down_excel)) {
            $result = $result->orderBy('id', 'DESC')->paginate($perPage);
            return $response->withJson($result);
        } else {
            $result = $result->orderBy('id', 'DESC')->get()->toArray();
            $header = ['会员账号', '真实姓名', '银行卡号', '开户银行', '出款金额', '出款平台', '出款时间', '订单号', '出款流水号', '支付状态', '备注', '操作人'];
            $datas  = [];
            foreach ($result as $val) {
                $platform = '未知';
                if ($val['platform_type'] == '1') {
                    $platform = '天付宝';
                } elseif ($val['platform_type'] == '2') {
                    $platform = '雅付';
                } elseif ($val['platform_type'] == '3') {
                    $platform = '金海哲';
                } elseif ($val['platform_type'] == '4') {
                    $platform = '泽圣';
                } elseif ($val['platform_type'] == '5') {
                    $platform = '传化';
                } elseif ($val['platform_type'] == '6') {
                    $platform = '开联通';
                } elseif ($val['platform_type'] == '7') {
                    $platform = '众点';
                } elseif ($val['platform_type'] == '8') {
                    $platform = '商码付';
                } elseif ($val['platform_type'] == '9') {
                    $platform = '喜付';
                } elseif ($val['platform_type'] == '10') {
                    $platform = '艾付';
                } elseif ($val['platform_type'] == '11') {
                    $platform = 'Nong付';
                } elseif ($val['platform_type'] == '12') {
                    $platform = '顺心付';
                } elseif ($val['platform_type'] == '14') {
                    $platform = '迅捷付';
                } elseif ($val['platform_type'] == '15') {
                    $platform = '多宝';
                } elseif ($val['platform_type'] == '16') {
                    $platform = 'Bingopay';
                } elseif ($val['platform_type'] == '17') {
                    $platform = '易宝';
                } elseif ($val['platform_type'] == '18') {
                    $platform = '新欣聚';
                } elseif ($val['platform_type'] == '19') {
                    $platform = '佳友通';
                } elseif ($val['platform_type'] == '20') {
                    $platform = '盖亚';
                } elseif ($val['platform_type'] == '21') {
                    $platform = '青英';
                } elseif ($val['platform_type'] == '22') {
                    $platform = '极云';
                }
                $state = '未处理';
                if (isset($val['platform_status'])) {
                    if ($val['platform_status'] == '1') {
                        $state = '处理成功';
                    } elseif ($val['platform_status'] == '2') {
                        $state = '处理中';
                    } elseif ($val['platform_status'] == '3') {
                        $state = '处理失败';
                    } elseif ($val['platform_status'] == '4') {
                        $state = '已退汇';
                    } elseif ($val['platform_status'] == '5') {
                        $state = '其他';
                    }
                }
                array_push($datas, [
                    0  => $val['account'] ?? '',
                    1  => $val['realname'] ?? '',
                    2  => $val['bank_card'] ?? '',
                    3  => $val['bank_name'] ?? '',
                    4  => $val['amount'] ?? '',
                    5  => $platform,
                    6  => $val['pay_out_time'] ?? '',
                    7  => $val['order_no'] ?? '',
                    8  => $val['platform_order_no'] ?? '',
                    9  => $state,
                    10 => $val['remark'] ?? '',
                    11 => $val['user'] ?? '',
                ]);
            }

            $filename = '会员出款记录-' . date('Ymd') . '.xls';

            $this->downExcel($header, $datas, $filename); //父类继承
        }
    }

    /**
     * @api {get} /admin/queryPayOutStatus?orderNo=:orderNo&type=:type 查询出款平台出款状态
     * @apiName QueryPayOutStatus
     * @apiGroup PayOut
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} orderNo 出款单号
     * @apiParam {Number} type 出款类型，0 为自动，1 为人工，默认不传为 0
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "acct_id": "6214****7963",   // 银行卡号
     *       "acct_name": "黄泽界",       // 姓名
     *       "bank_name": "招商银行",     // 银行名称
     *       "business_type": "20101",    // 业务类型，20101 业务往来款 20102 员工工资 20103 报销 20104 合作款项 20105 赔付保金 20999 其他
     *       "cur_type": "1",             // 金额类型，1 人民币
     *       "memo": "提现",              // 备注
     *       "serialno_desc": "商户可用余额不足",   // 返回消息
     *       "serialno_state": "3",                 // 状态，1 处理成功、2 处理中、3 处理失败、4 已退汇
     *       "sp_serialno": "201709191409112980911",   // 出款单号
     *       "spid": "1800218655",                     // 商户ID
     *       "tfb_rsptime": "20170919165433",          // 出款时间
     *       "tfb_serialno": "2017091918260274",       // 流水号
     *       "tran_amt": "5000",                       // 出款金额，单位：分
     *       "sign": "1263060d488cea252c6b2b72f0a6eadf"  // 数据签名
     *   }
     */
    public function queryPayOutStatus(Request $request, Response $response, $args)
    {
        $getDatas    = $request->getQueryParams();
        $orderNo     = $getDatas['orderNo'] ?? '';
        $tfbSerialNo = $getDatas['tfbOrderNo'] ?? '';
        $type        = $getDatas['type'] ?? 0;
        if ($orderNo) {
            $result            = [];
            $platform_order_no = '';
            $platform_status   = 0;
            $payOut            = null;
            if ($type == 0) {
                // 自动
                $payOut = PayOut::where('order_no', '=', $orderNo)->first();
            } elseif ($type == 1) {
                // 人工
                $payOut = Withdrawal::where('order_no', '=', $orderNo)->first();
            }
            $platform   = Platform::where('id', '=', $payOut->platform_id)->first();
            $config     = ['parterNo' => $platform->no, 'parterKey' => $platform->key];
            $payOutType = $platform->pay_out_type;
            if ($payOutType == 1) {
                $result = Tianfubao::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo, 'tfbSerialNo' => $tfbSerialNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['tfb_serialno'] ?? '';
                $platform_status   = $result['serialno_state'] ?? 2;
                // 统一状态
                if ($platform_status == 0) {
                    $platform_status = 0;
                } elseif ($platform_status == 1) {
                    $platform_status = 1;
                } elseif ($platform_status == 2) {
                    $platform_status = 2;
                } elseif ($platform_status == 3) {
                    $platform_status = 3;
                } elseif ($platform_status == 4) {
                    $platform_status = 4;
                } else {
                    $platform_status = 5;
                }
                $result['ckType'] = 1;
            } elseif ($payOutType == 2) {
                $result = Yafu::getInstance($config)->generateSignature([
                    'merOrderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['casOrdNo'] ?? '';
                $platform_status   = $result['orderStatus'] ?? 4;
                // 统一状态
                if ($platform_status == 0) {
                    $platform_status = 0;
                } elseif ($platform_status == 1) {
                    $platform_status = 1;
                } elseif ($platform_status == 2) {
                    $platform_status = 4;
                } elseif ($platform_status == 3) {
                    $platform_status = 3;
                } elseif ($platform_status == 4) {
                    $platform_status = 2;
                } else {
                    $platform_status = 5;
                }
                $result['ckType'] = 2;
            } elseif ($payOutType == 3) {
                $result = Jinhaizhe::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['sy_request_no'] ?? '';
                $platform_status   = $result['status'] ?? 1;
                // 统一状态
                if ($platform_status == 0) {
                    $platform_status = 0;
                } elseif ($platform_status == 1) {
                    $platform_status = 2;
                } elseif ($platform_status == 2) {
                    $platform_status = 1;
                } elseif ($platform_status == 3) {
                    $platform_status = 3;
                } elseif ($platform_status == 4) {
                    $platform_status = 4;
                } else {
                    $platform_status = 5;
                }
                if (!isset($result['origData'])) {
                    $result['origData']['ckType'] = 3;
                } else {
                    $result['ckType'] = 3;
                }
            } elseif ($payOutType == 4) {
                $result = Zesheng::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['orderId'] ?? '';
                $platform_status   = $result['state'] ?? '01';
                // 统一状态 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
                if ($platform_status == '00') {
                    $platform_status = 1;
                } elseif ($platform_status == '01' || $platform_status == '90') {
                    $platform_status = 2;
                } elseif ($platform_status == '02') {
                    $platform_status = 3;
                } else {
                    $platform_status = 5;
                }
                $result['ckType'] = 4;
            } elseif ($payOutType == 5) {
                $result = Chuanhua::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['data']['businessrecordnumber'] ?? '';
                $platform_status   = $result['data']['status'] ?? '';
                // 统一状态 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
                if (strpos($platform_status, '成功') !== false) {
                    $platform_status = 1;
                } elseif (strpos($platform_status, '处理中') !== false) {
                    $platform_status = 2;
                } elseif (strpos($platform_status, '失败') !== false) {
                    $platform_status = 3;
                } elseif (strpos($platform_status, '已退票') !== false) {
                    $platform_status = 4;
                } else {
                    $platform_status = 5;
                }
                $result['ckType'] = 5;
            } elseif ($payOutType == 6) {
                $result = KaiLianTong::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = '';
                $platform_status   = $result['response']['envelope']['body']['status'] ?? 'TX_BEGIN';
                // 统一状态 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
                if ($platform_status == 'TX_SUCCESS') {
                    $platform_status = 1;
                } elseif ($platform_status == 'CREATED' || $platform_status == 'TX_BEGIN') {
                    $platform_status = 2;
                } elseif ($platform_status == 'TX_FAIL') {
                    $platform_status = 3;
                } elseif ($platform_status == 'INVALID') {
                    $platform_status = 5;
                }
                $result['ckType'] = 6;
            } elseif ($payOutType == 7) {
                $result = Zhongdian::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['transaction_id'];
                $platform_status   = $result['result_code'] ?? '';
                // 统一状态 0 成功、非0失败
                if ($platform_status == 0) {
                    $platform_status = 1;
                } else {
                    $platform_status = 5;
                }
                $result['ckType'] = 7;
            } elseif ($payOutType == 8) {
                $result = Shangma::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $platform_order_no = $result['outTradeNo'];
                $platform_status   = $result['returnCode'] ?? -1;
                // 统一状态 0 成功、非0失败
                if ($platform_status == 0) {
                    $platform_status = 0;
                } elseif ($platform_status == 1) {
                    $platform_status = 2;
                } elseif ($platform_status == 2) {
                    $platform_status = 1;
                } elseif ($platform_status == -1) {
                    $platform_status = 3;
                } elseif ($platform_status == -2) {
                    $platform_status = 5;
                }
                $result['ckType'] = 8;
            } elseif ($payOutType == 9) {
                $result = Xifu::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultCode        = $result['respCode'] ?? 'F9999';
                $platform_order_no = $result['batchNo'] ?? '';
                $batchContent      = $result['batchContent'] ?? '';
                $returnCode        = explode(',', $batchContent);
                // 统一状态 0 成功、非0失败
                $platform_status = 5;
                if ($resultCode == 'S0001' && empty($returnCode[12])) {
                    $platform_status = 2;
                } elseif ($resultCode == 'S0001' && strpos($returnCode[12], '成功') !== false) {
                    $platform_status = 1;
                } elseif ($resultCode == 'S0001' && strpos($returnCode[12], '失败') !== false) {
                    $platform_status = 3;
                }
                $result['ckType'] = 9;
            } elseif ($payOutType == 10) {
                $result = Aifu::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultCode        = $result['result_code'] ?? '';
                $platform_order_no = $result['order_no'] ?? '';
                $batchContent      = $result['result'] ?? 'H';
                // 统一状态 0 成功、非0失败
                $platform_status = 5;
                if ($resultCode == '000000' && $batchContent == 'H') {
                    $platform_status = 2;
                } elseif ($resultCode == '000000' && $batchContent == 'S') {
                    $platform_status = 1;
                } elseif ($resultCode == '000000' && $batchContent == 'F') {
                    $platform_status = 3;
                }
                $result['ckType'] = 10;
            } elseif ($payOutType == 11) {
                $result = Nongfu::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultCode        = $result['code'] ?? '1002';
                $platform_order_no = $result['merOrderId'] ?? '';
                $resultFlag        = $result['success'] ?? 0;
                // 统一状态 0 成功、非0失败
                $platform_status = 3;
                if ($resultFlag == 1) {
                    if ($resultCode == '1000') {
                        $platform_status = 0;
                    } elseif ($resultCode == '1002') {
                        $platform_status = 3;
                    } elseif ($resultCode == '1001') {
                        $platform_status = 1;
                    } elseif ($resultCode == '1111') {
                        $platform_status = 2;
                    } else {
                        $platform_status = 5;
                    }
                }
                $result['ckType'] = 11;
            } elseif ($payOutType == 12) {
                $result = Shunxin::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultCode        = $result['respCode'] ?? '05';
                $platform_order_no = $result['batchNo'] ?? '';
                $resultFlag        = $result['Status'] ?? 2;
                // 统一状态 0 成功、非0失败
                $platform_status = 3;
                if ($resultCode == '00') {
                    if ($resultFlag == 0) {
                        $platform_status = 2;
                    } elseif ($resultFlag == 1) {
                        $platform_status = 1;
                    } elseif ($resultFlag == 2) {
                        $platform_status = 3;
                    } elseif ($resultFlag == 5) {
                        $platform_status = 2;
                    } else {
                        $platform_status = 5;
                    }
                }
                $result['ckType'] = 12;
            } elseif ($payOutType == 14) {
                $result = Xunjie::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $respCode          = $result['respCode'] ?? '';
                $resultFlag        = $result['oRespCode'] ?? '';
                $platform_order_no = $result['serialId'] ?? '';
                // 统一状态 0 成功、非0失败
                $platform_status = 3;
                if ($resultFlag == '0000' && $respCode == '0000') {
                    $platform_status = 1;
                } elseif ($resultFlag == 'P000' || $resultFlag == 'P999') {
                    $platform_status = 2;
                } elseif ($resultFlag == '9996') {
                    $platform_status = 4;
                } elseif ($resultFlag == '9998') {
                    $platform_status = 3;
                } elseif ($resultFlag == '9999') {
                    $platform_status = 5;
                }
                $result['ckType'] = 14;
            } elseif ($payOutType == 15) {
                $orderNo = $payOut->platform_order_no;
                $result  = Duobao::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $respCode          = $result['status'] ?? '';
                $resultFlag        = $result['settlestatus'] ?? '';
                $platform_order_no = $result['settleid'] ?? '';
                // 统一状态 0 成功、非0失败
                $platform_status = 3;
                if (strpos($resultFlag, '已支付') !== false) {
                    $platform_status = 1;
                } elseif (strpos($resultFlag, '支付中') !== false) {
                    $platform_status = 2;
                } elseif (strpos($resultFlag, '已拒绝') !== false) {
                    $platform_status = 4;
                } elseif (strpos($resultFlag, '代付失败') !== false) {
                    $platform_status = 3;
                } elseif (strpos($resultFlag, '审核中') !== false) {
                    $platform_status = 0;
                } else {
                    $platform_status = 5;
                }
                $result['ckType'] = 15;
            } elseif ($payOutType == 16) {
                $result = Bingo::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultFlag        = $result['status'] ?? '';
                $respCode          = $result['msg'] ?? '';
                $platform_order_no = $result['plat_order_sn'] ?? '';
                // 统一状态 0 成功、非0失败
                $platform_status = 3;
                if ($resultFlag == 3) {
                    $platform_status = 1;
                } elseif ($resultFlag == 1) {
                    $platform_status = 2;
                }
                $result['ckType'] = 16;
            } elseif ($payOutType == 17) {
                $result = Yibao::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $ret_Code          = $result['ret_Code'] ?? '';
                $resultFlag        = $result['r1_Code'] ?? '';
                $respCode          = $result['fail_Desc'] ?? '';
                $platform_order_no = '';
                // 统一状态 0 成功、非0失败
                $platform_status = 3;
                if ($ret_Code == 1) {
                    $platform_status = 2;
                    if ($resultFlag == '0026') {
                        $platform_status = 1;
                    } elseif ($resultFlag == '0027') {
                        $platform_status = 4;
                    } elseif ($resultFlag == '0028') {
                        $platform_status = 3;
                    }
                }
                $result['ckType'] = 17;
            } elseif ($payOutType == 18) {
                $result = Xinxinju::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultFlag        = $result['field039'] ?? '';
                $platform_order_no = $result['field062'] ?? '';
                // 统一状态 0 成功、非0失败
                $platform_status = 2;
                if ($resultFlag == '00') {
                    $platform_status = 1;
                } elseif ($resultFlag == '01') {
                    $platform_status = 2;
                } elseif ($resultFlag == '02') {
                    $platform_status = 2;
                } elseif ($resultFlag == '03') {
                    $platform_status = 3;
                }
                $result['ckType'] = 18;
            } elseif ($payOutType == 19) {
                $result = Jiayoutong::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();

                $result['ckType'] = 19;
            } elseif ($payOutType == 20) {     //盖亚
                $result = gaiya::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultFlag        = $result['success'] ?? '';
                $resultStatus        = $result['transStatus'] ?? '';//交易状态
                $platform_order_no = $result['merOrderId'] ?? '';
                // 统一状态 0 成功、非0失败
                $platform_status = 2;

                if($resultFlag == 1){
                    if($resultStatus == 1){
                        $platform_status = 1;
                    }elseif($resultStatus == 2){
                        $platform_status = 3;
                    }elseif($resultStatus == 3){
                        $platform_status = 2;
                    }
                }

                $result['ckType'] = 20;
            }elseif ($payOutType == 21) {   //青英
                $result = Qingying::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultFlag        = $result['field039'] ?? '';     //应答码
                $platform_order_no = $result['field062'] ?? '';     //平台订单号
                // 统一状态 0 成功、非0失败
                // 统一状态 0 成功、非0失败
                $platform_status = 2;

                if ($resultFlag == '00') {
                    $platform_status = 1;
                } elseif ($resultFlag == '01') {
                    $platform_status = 2;
                } elseif ($resultFlag == '02') {
                    $platform_status = 2;
                } elseif ($resultFlag == '03') {
                    $platform_status = 3;
                }
                $result['ckType'] = 21;
            }elseif ($payOutType == 22) {   //青英
                $result = Jiyun::getInstance($config)->generateSignature([
                    'orderNo' => $orderNo,
                ], 'query')->sendRequest();
                $resultFlag        = $result['field039'] ?? '';     //应答码
                $platform_order_no = $result['field062'] ?? '';     //平台订单号
                // 统一状态 0 成功、非0失败
                // 统一状态 0 成功、非0失败
                $platform_status = 2;

                if ($resultFlag == '00') {
                    $platform_status = 1;
                } elseif ($resultFlag == '01') {
                    $platform_status = 2;
                } elseif ($resultFlag == '02') {
                    $platform_status = 2;
                } elseif ($resultFlag == '03') {
                    $platform_status = 3;
                }
                $result['ckType'] = 22;
            }
            // 已完成的就不要更新状态了
            if ($payOut->platform_status != 1) {
                $payOut->platform_id = $platform->id;
                // $payOut->platform_order_no = $platform_order_no;
                // $payOut->platform_status   = $platform_status;
                $payOut->platform_attach = json_encode($result);
                $payOut->save();
            }

            // 已完成的就不要更新状态了 PS: 已经改为队列轮询查询出款状态了，故以下被注释了
            if ($platform_status == 1) {
                // 自动
                if ($type == 0) {
                    $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
                    // 交易成功，修改 BBIN 后台出款单状态为 1 确定
                    $crawlerRes = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateStatus',
                        'id'      => $payOut->wid,
                        'status'  => 1, // 确定
                        'account' => $payOut->account,
                    ]);
                    if (strpos($crawlerRes, 'true') !== false) {
                        // 更新 确定 状态
                        $payOut->status = 1;
                    }
                    // 备注
                    $crawlerRes = Crawler::getInstance()->updatePayOutData($crawlerUrl, [
                        'act'     => 'updateRemark',
                        'id'      => $payOut->wid,
                        'content' => '出款成功',
                    ]);
                }

                $payOut->platform_id       = $platform->id;
                $payOut->platform_order_no = $platform_order_no;
                $payOut->platform_status   = $platform_status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
                $payOut->platform_attach   = json_encode($result);
                $payOut->save();
            }
        }
        // 格式化数据
        $res = [];
        if (!isset($result['origData'])) {
            $res['origData'] = $result;
            if ($result['ckType'] == 5 && isset($result['data'])) {
                $res['origData']['businessrecordnumber'] = $result['data']['businessrecordnumber'];
                $res['origData']['status']               = $result['data']['status'];
                unset($res['origData']['data']);
            } elseif ($result['ckType'] == 6) {
                unset($res['origData']['response']);
                $res['origData'] = array_merge(
                    $result['response']['envelope']['head'],
                    $result['response']['envelope']['body'],
                    $res['origData']
                );
            } elseif (in_array($result['ckType'], [7, 8, 9, 10, 11, 12, 14, 15, 16, 17, 18, 19])) {
                $res['origData'] = $result;
            }
        } else {
            $res                       = $result;
            $res['origData']['ckType'] = $result['ckType'];
            unset($res['ckType']);
        }
        return $response->withJson($res);
    }

    /**
     * @api {post} /admin/updateRemark/:id 修改出款备注(与BBIN、GPK同步)
     * @apiName UpdateRemark
     * @apiGroup PayOut
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id BBIN出款记录id
     *
     * @apiParam {String} content 备注内容
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "修改成功",
     *       "data": []
     *   }
     */
    public function updateRemark(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '修改失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $id        = $args['id'] ?? 0;
        $content   = $postDatas['content'] ?? '';
        if ($id > 0 && $content) {
            $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
            $ret        = Crawler::getInstance()->updatePayOutData(
                $crawlerUrl,
                [
                    'act'     => 'updateRemark',
                    'id'      => $id,
                    'content' => $content,
                ]
            );
            if (strpos($ret, 'true') !== false) {
                PayOut::where('wid', '=', $id)->update([
                    'remark'  => $content,
                    'user_id' => $this->jwt->userInfo->id,
                    'user'    => $this->jwt->userInfo->username,
                ]);
                $result['status'] = 0;
                $result['msg']    = '修改成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateStatus/:id 修改出款状态(与BBIN、GPK同步)
     * @apiName UpdateStatus
     * @apiGroup PayOut
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id BBIN出款记录id
     *
     * @apiParam {Number} status 状态: 1 确定、 2 取消、 3 拒绝、 4 锁定、 0 解锁
     * @apiParam {String} account 会员账号
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "修改成功",
     *       "data": []
     *   }
     */
    public function updateStatus(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '修改失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $id        = $args['id'] ?? 0;
        $status    = $postDatas['status'] ?? null;
        $account   = $postDatas['account'] ?? '';
        if ($id > 0 && $status >= 0 && $account) {
            $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
            $ret        = Crawler::getInstance()->updatePayOutData(
                $crawlerUrl,
                [
                    'act'     => 'updateStatus',
                    'id'      => $id,
                    'status'  => $status,
                    'account' => $account,
                ]
            );
            if (strpos($ret, 'true') !== false) {
                PayOut::where('wid', '=', $id)->update([
                    'platform_status' => 1,
                    'status'          => $status,
                    'user_id'         => $this->jwt->userInfo->id,
                    'user'            => $this->jwt->userInfo->username,
                ]);
                $result['status'] = 0;
                $result['msg']    = '修改成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 自动出款，通过crontab设置一个定时任务，访问此地址，查询处理中的出款单状态，自动点击bbin确定按钮
     */
    public function checkPayOutStatus(Request $request, Response $response, $args)
    {
        $tmpArr = [];

        // 只处理 24 小时之内的出款单
        $dateTime = date('Y-m-d H:i:s', strtotime("-24 hours"));

        // 自动
        $payOuts = PayOut::where('platform_status', '=', 2)->where('created_at', '>=', $dateTime)->limit(20)->get();
        if ($payOuts) {
            foreach ($payOuts as $payOut) {
                $prevToken = $payOut->job_id;
                $status    = new Resque_Job_Status($prevToken);
                $jobStatus = $status->get(); // 1 等待、2 进行中、3 失败、4 完成、false token无效
                // 队列里不存在、失败、完成的这3种情况就再次查询出款状态，防止队列拥堵
                if ($jobStatus === false || ($jobStatus !== false && in_array($jobStatus, [3, 4]))) {
                    $orderNo        = $payOut->order_no;
                    $token          = Resque::enqueue('payoutChecker', AutoPayOutCheckJob::class, ['orderNo' => $orderNo], true);
                    $payOut->job_id = $token;
                    if ($payOut->save()) {
                        array_push($tmpArr, $orderNo);
                    }
                }
            }
        }

        // 人工
        $withdrawals = Withdrawal::where('status', '=', 2)->where('created_at', '>=', $dateTime)->get();
        if ($withdrawals) {
            foreach ($withdrawals as $withdrawal) {
                $prevToken = $withdrawal->job_id;
                $status    = new Resque_Job_Status($prevToken);
                $jobStatus = $status->get(); // 1 等待、2 进行中、3 失败、4 完成、false token无效
                // 队列里不存在、失败、完成的这3种情况就再次查询出款状态，防止队列拥堵
                if ($jobStatus === false || ($jobStatus !== false && in_array($jobStatus, [3, 4]))) {
                    $orderNo            = $withdrawal->order_no;
                    $token              = Resque::enqueue('manualPayoutChecker', ManualPayOutCheckJob::class, ['orderNo' => $orderNo], true);
                    $withdrawal->job_id = $token;
                    if ($withdrawal->save()) {
                        array_push($tmpArr, $orderNo);
                    }
                }
            }
        }

        return $response->withJson($tmpArr);
    }

    /**
     * 判断某个会员是否被限制了出款次数，仅适用于BBIN平台
     * @param  String  $account 会员账号
     * @return boolean true为可出款，反之不可以出款
     */
    private function isPossiblePayOut($account)
    {
        $flag = true;
        if ($account) {
            $account = trim($account);
            $member  = Member::select(['level_id'])->where('account', '=', $account)->first();
            if ($member) {
                $levelId     = $member->level_id;
                $payOutLimit = PayOutLimit::select(['count'])->whereRaw("FIND_IN_SET({$levelId}, `level_ids`)")->first();
                if ($payOutLimit) {
                    $count = $payOutLimit->count;
                    // 美东时间
                    $dateTime    = Utils::myGMDate('Y-m-d', time(), -4);
                    $payOutCount = PayOut::select(['id'])->whereRaw("`account` = '{$account}' AND DATE_FORMAT(`pay_out_time`,'%Y-%m-%d') = '{$dateTime}'")->count();
                    if ($payOutCount >= $count) {
                        $flag = false;
                    }
                }
            }
        }
        return $flag;
    }

    /**
     * 判断某个会员是否被限制了出款次数，仅适用于GPK平台
     * @param  String  $levelId 会员层级ID
     * @return boolean true为可出款，反之不可以出款
     */
    private function isPossiblePayOutGPK($levelId)
    {
        $flag = true;
        if ($levelId > 0) {
            $payOutLimit = PayOutLimit::select(['count'])->whereRaw("FIND_IN_SET({$levelId}, `level_ids`)")->first();
            if ($payOutLimit) {
                $count = $payOutLimit->count;
                // 美东时间
                $dateTime    = Utils::myGMDate('Y-m-d', time(), -4);
                $payOutCount = PayOut::select(['id'])->whereRaw("`account` = '{$account}' AND DATE_FORMAT(`pay_out_time`,'%Y-%m-%d') = '{$dateTime}'")->count();
                if ($payOutCount >= $count) {
                    $flag = false;
                }
            }
        }
        return $flag;
    }
}
