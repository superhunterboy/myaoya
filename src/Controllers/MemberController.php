<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Crawler;
use \Weiming\Libs\Utils;
use \Weiming\Models\Level;
use \Weiming\Models\Member;
use \Weiming\Models\ReportArtificialDeposit;
use \Weiming\Models\ReportPayCompany;
use \Weiming\Models\ReportPayOnline;

class MemberController extends BaseController
{
    /**
     * @api {post} /getMemberRecharge 获取会员充值记录
     * @apiName GetMemberRecharge
     * @apiGroup Member
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiParam {String} account 会员账号
     * @apiParam {String} datetime 充值起始日期
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": {
     *           "online": [
     *               {
     *                   "id": 39714,
     *                   "account": "lei888888",
     *                   "amount": "18.00",
     *                   "order_no": "201712010708830413"
     *               }
     *           ],
     *           "company": [
     *               {
     *                   "id": 1173,
     *                   "account": "lei888888",
     *                   "amount": "10.00",
     *                   "order_no": "2017101872786386"
     *               }
     *           ],
     *           "artificial": [
     *               {
     *                   "id": 9,
     *                   "account": "lei888888",
     *                   "amount": "20.00",
     *                   "order_no": "2017101712182645873"
     *               }
     *           ]
     *       }
     *   }
     */
    public function getMemberRecharge(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '', 'data' => []];
        $postDatas = $request->getParsedBody();
        $account   = $postDatas['account'] ?? '';
        $datetime  = $postDatas['datetime'] ?? ''; // 需要转换为美东时间
        if ($account && $datetime) {
            $datetime          = Utils::myGMDate('Y-m-d H:i:s', strtotime($datetime), -4);
            $payOnline         = ReportPayOnline::select(['id', 'account', 'amount', 'order_no', 'time'])->whereRaw("`account` = '{$account}' AND `time` >= '{$datetime}'")->orderBy('time', 'ASC')->get();
            $payCompany        = ReportPayCompany::selectRaw("`id`, `account`, `amount`, `order_no`, `operation_datetime` AS `time`")->whereRaw("`account` = '{$account}' AND `operation_datetime` >= '{$datetime}'")->orderBy('operation_datetime', 'ASC')->get();
            $artificialDeposit = ReportArtificialDeposit::select(['id', 'account', 'amount', 'order_no', 'time'])->whereRaw("`account` = '{$account}' AND `time` >= '{$datetime}'")->orderBy('time', 'ASC')->get();
            $result['status']  = 0;
            $result['data']    = [
                'online'     => $payOnline,
                'company'    => $payCompany,
                'artificial' => $artificialDeposit,
            ];
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /memberIsExists/:account 判断是否是会员
     * @apiName MemberIsExists
     * @apiGroup Member
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiParam {String} account 会员账号
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "您输入的账号已是会员，可继续充值！",
     *       "data": {
     *           "id": 51470,
     *           "uid": 289033545,
     *           "account": "ceshi1",
     *           "level_id": 12892,
     *           "register_time": "2017-09-16 03:36:08",
     *           "status": "启用",
     *           "created_at": "2017-10-07 10:45:35",
     *           "updated_at": "2017-12-20 08:37:47",
     *           "level": {
     *               "id": 12892,
     *               "name": "测试层级",
     *               "status": "1",
     *               "created_at": "2017-12-13 15:36:36",
     *               "updated_at": "2017-12-26 08:58:41"
     *           }
     *       }
     *   }
     */
    public function memberIsExists(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '您的账户暂时无法使用快捷支付，建议您先使用其他方式进行存款，谢谢！', 'data' => []];
        $account = trim($args['account']);
        if ($account) {
            $member = Member::with(['level'])->where('account', '=', $account);
            if ($member->count()) {
                $result['status'] = 0;
                $result['msg']    = '您输入的账号已是会员，可继续充值！';
                $result['data']   = $member->first();
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/pullHistoryMembers?currPage=:currPage 拉取会员数据
     * @apiName PullHistoryMembers
     * @apiGroup Member
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} currPage 页数 7 ~ 最大页数之间
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "提交成功，拉取数据需要一些时间",
     *       "data": []
     *   }
     */
    public function pullHistoryMembers(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '提交失败', 'data' => []];
        $getDatas = $request->getQueryParams();
        $currPage = $getDatas['currPage'] ?? '';
        if ($currPage) {
            $crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
            $ret        = Crawler::getInstance()->updatePayOutData(
                $crawlerUrl,
                [
                    'act'      => 'pullHistoryMembers',
                    'currPage' => $currPage,
                ]
            );
            if (strpos($ret, 'true') !== false) {
                $result['status'] = 0;
                $result['msg']    = '提交成功，拉取数据需要一些时间';
            }
        }
        return $response->withJson($result);
    }

    public function addMembers(Request $request, Response $response, $args)
    {
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('Member Data.', $postDatas);
        if (isset($postDatas['jsonData']) && $postDatas['jsonData']) {
            // 层级
            $memberLevels = $this->getMemberLevels();
            $tmpArr       = json_decode($postDatas['jsonData'], true);
            foreach ($tmpArr as $key => $val) {
                // Array
                // (
                //     [0] => 96434532 // 会员id
                //     [1] => ajstest // 上层账号
                //     [2] => 測試股東 // 名称
                //     [3] => bjstest  // 账号
                //     [4] => 股东 // 层级
                //     [5] => 人民币 // 币别
                //     [6] => 0.00 // BB额度
                //     [7] => 1 // 下属人数统计
                //     [8] => 2015-09-21 03:25:57 // 新增日期
                //     [9] => Array
                //            (
                //                [0] => 停用
                //                [1] => 冻结
                //                [2] => 停权
                //            )
                // )
                $levelName    = trim($val[4]);
                $uid          = $val[0] ?? '';
                $account      = $val[3] ?? '';
                $levelId      = $memberLevels[$levelName] ?? 0;
                $registerTime = $val[8] ?? '';
                $status       = implode(',', $val[9]);
                $remark       = json_encode($val);
                // $account      = trim($account, ' ');
                $member = Member::updateOrCreate(['account' => $account], [
                    'uid'           => $uid,
                    'account'       => $account,
                    'level_id'      => $levelId,
                    'register_time' => $registerTime,
                    'status'        => $status,
                    'remark'        => $remark,
                ]);
                // if ($member && $member->wasRecentlyCreated) {
                // }
            }
            $response->getBody()->write("Ok, Members data has been submitted to the payment system.\n");
        }
        return $response;
    }

    public function updateMembersLevel(Request $request, Response $response, $args)
    {
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('Update Members Level Data.', $postDatas);
        if (isset($postDatas['jsonData']) && $postDatas['jsonData']) {
            $tmpArr = json_decode($postDatas['jsonData'], true);
            foreach ($tmpArr as $levelId => $users) {
                $userIds = [];
                foreach ($users as $user) {
                    array_push($userIds, $user['userID']);
                }
                Member::whereIn('uid', $userIds)->update(['level_id' => $levelId]);
            }
            $response->getBody()->write("Ok, Update members level data has been submitted to the payment system.\n");
        }
        return $response;
    }

    public function getNotLevelMembers(Request $request, Response $response, $args)
    {
        return $response->withJson(Member::select(['uid', 'account'])->where('level_id', '=', 0)->get()->toArray());
    }

    private function getMemberLevels()
    {
        $retArr = [];
        $levels = Level::all()->toArray();
        if ($levels) {
            foreach ($levels as $level) {
                $levelName          = trim($level['name']);
                $retArr[$levelName] = $level['id'];
            }
        }
        return $retArr;
    }
}