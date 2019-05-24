<?php

namespace Weiming\Controllers;

use Mockery\CountValidator\Exception;
use Weiming\Models\BankCards;
use Weiming\Models\Picture;
use Weiming\Models\PrivateQrcode;
use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Models\Channel;
use \Weiming\Models\Member;
use \Weiming\Models\PaymentChannel;

class PaymentChannelController extends BaseController
{

    public function addPaymentChannel(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => ''];

        $postDatas = $request->getParsedBody();

        if (empty($postDatas['platform']) || empty($postDatas['channel'])) {

            $result['msg'] = '支付平台和支付方式不能为空';

        } else {
            $res = PaymentChannel::create([
                'platform'           => $postDatas['platform'] ?? '',
                'platform_identifer' => $postDatas['platform_identifer'] ?? '',
                'channel'            => $postDatas['channel'] ?? '',
                'paycode'            => $postDatas['paycode'] ?? '',
                'merchant_no'        => $postDatas['merchant_no'] ?? '',
                'key'                => $postDatas['key'] ?? '',
                'display_name'       => $postDatas['display_name'],
                'position'           => $postDatas['position'],
                'offline_category'   => $postDatas['offline_category'] ?? '',
                'deposit_range'      => $postDatas['deposit_range'] ?? '',
                'callback_url'       => $postDatas['callback_url'] ?? '',
                'notify_url'         => $postDatas['notify_url'] ?? '',
                'status'             => $postDatas['status'],
                'remark'             => $postDatas['remark'] ?? '',
                'sequence'           => $postDatas['sequence'] ?? 0,
            ]);

            if ($res->wasRecentlyCreated) {
                $result['status'] = 0;
                $result['msg']    = '创建成功';
            } else {
                $result['msg'] = '支付渠道重复，请重新输入';
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function updatePaymentChannel(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $postDatas = $request->getParsedBody();

        if (empty($postDatas['platform']) || empty($postDatas['channel'])) {

            $result['msg'] = '支付平台和支付方式不能为空';

        } else {
            $oPaymentChannel                     = PaymentChannel::find($args['id']);
            $oPaymentChannel->platform           = $postDatas['platform'];
            $oPaymentChannel->platform_identifer = $postDatas['platform_identifer'];
            $oPaymentChannel->channel            = $postDatas['channel'] ?? '';
            $oPaymentChannel->paycode            = $postDatas['paycode'] ?? '';
            $oPaymentChannel->merchant_no        = $postDatas['merchant_no'] ?? '';
            $oPaymentChannel->key                = $postDatas['key'] ?? '';
            $oPaymentChannel->display_name       = $postDatas['display_name'];
            $oPaymentChannel->position           = $postDatas['position'];
            if ($postDatas['position'] == 2 && !empty($postDatas['offline_category'])) {
                $oPaymentChannel->offline_category = $postDatas['offline_category'];
            } else {
                $oPaymentChannel->offline_category = '';
            }

            $oPaymentChannel->deposit_range = $postDatas['deposit_range'] ?? '';
            $oPaymentChannel->callback_url  = $postDatas['callback_url'] ?? '';
            $oPaymentChannel->notify_url    = $postDatas['notify_url'] ?? '';
            $oPaymentChannel->status        = $postDatas['status'];
            $oPaymentChannel->remark        = $postDatas['remark'] ?? '';
            $oPaymentChannel->sequence      = $postDatas['sequence'] ?? 0;
            $res                            = $oPaymentChannel->save();

            if ($res) {

                $result['status'] = 0;
                $result['msg']    = '更新成功';
            } else {
                $result['msg'] = '更新失败';
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function switchStatus(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $postDatas = $request->getParsedBody();

        $res = PaymentChannel::find($args['id'])->update(['status' => $postDatas['status']]);
        if ($res) {

            $result['status'] = 0;
            $result['msg']    = '更新成功';
        } else {
            $result['msg'] = '更新失败';
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function delPaymentChannel(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '', 'data' => []];

        $res = PaymentChannel::destroy($args['id']);

        if ($res) {

            $result['status'] = 0;
            $result['msg']    = '删除成功';

        } else {

            $result['msg'] = '删除失败';

        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function getPaymentChannels(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        $page        = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage     = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 10;
        $platform    = isset($getDatas['platform']) ? $getDatas['platform'] : '';
        $channel     = isset($getDatas['channel']) ? $getDatas['channel'] : '';
        $position    = isset($getDatas['position']) ? intval($getDatas['position']) : '';
        $merchant_no = isset($getDatas['merchant_no']) ? strval($getDatas['merchant_no']) : '';

        Paginator::currentPathResolver(function () {return "/admin/getPaymentChannels";});
        Paginator::currentPageResolver(function () use ($page) {return $page;});

        $result = PaymentChannel::whereRaw('1=1');
        if ($platform) {
            $result = $result->where('platform', $platform);
        }
        if ($channel) {
            $result = $result->where('channel', $channel);
        }
        if ($position) {
            $result = $result->where('position', $position);
        }
        if ($merchant_no) {
            $result = $result->where('merchant_no', $merchant_no);
        }
        $result    = $result->orderBy('status', 'desc')->orderBy('position', 'asc')->orderBy('sequence', 'asc')->paginate($perPage);
        $aChannels = [];
        $oChannels = Channel::all();
        foreach ($oChannels as $aChannel) {
            $aChannels[$aChannel->tag] = $aChannel->name;
        }
        foreach ($result as &$v) {
            $v->channel = $aChannels[$v->channel];
        }

        $response->getBody()->write($result->toJson());

        return $response;
    }

    public function pullPaymentChannels(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '账号不存在！', 'data' => []];
        $aChannels = [];
        $account   = trim($args['account']);
        $position  = trim($args['position']);
        $member    = Member::where('account', '=', $account)->exists();
        if ($member) {
            $oChannels = Channel::where('status', 1)->where('position', $position)->orderBy('sequence', 'asc')->orderBy('updated_at', 'desc')->get();
            foreach ($oChannels as $k => $oChannel) {
                $aChannels[$k]['id']          = $oChannel->id;
                $aChannels[$k]['name']        = $oChannel->name;
                $aChannels[$k]['tag']         = $oChannel->tag;
                $aChannels[$k]['channelList'] = [];
                $oPaymentChannels             = PaymentChannel::where('status', 1)->where('channel', $oChannel->tag)->orderBy('sequence', 'asc')->get();

                foreach ($oPaymentChannels as $oPaymentChannel) {
                    $isRange       = false;
                    $aDepositRange = [];
                    $depositRange  = $oPaymentChannel->deposit_range;
                    if ($depositRange) {
                        if (strpos($depositRange, '-') !== false) //如果是金额范围格式 比如1-5000
                        {
                            $isRange       = true;
                            $aDepositRange = explode('-', $depositRange);
                        } else //如果是金额范围格式 比如100,200,300
                        {
                            $aDepositRange = explode(',', $depositRange);
                        }
                    }
                    if ($oPaymentChannel->position == 1) //线上
                    {
                        $urlData = '';
                        if ($oPaymentChannel->channel == 'netbank') //如果是网银充值
                        {
                            try
                            {
                                $sPlatform                = "\Weiming\Libs\Payments\\" . $oPaymentChannel->platform_identifer;
                                $aChannels[$k]['netbank'] = $sPlatform::$aNetbank;
                            } catch (Exception $e) {
                                $result['msg'] = '支付渠道信息错误';
                                return $response->withJson($result);
                            }
                        }
                    } else {
                        $sChannel = $oPaymentChannel->channel;
                        if ($sChannel == 'wechat' || $sChannel == 'wechatWap') {
                            $type = 1;
                        } elseif ($sChannel == 'alipay' || $sChannel == 'alipayWap') {
                            $type = 2;
                        } elseif ($sChannel == 'qq' || $sChannel == 'qqWap') {
                            $type = 3;
                        } else {
                            $type = 4;
                        }

                        switch ($oPaymentChannel->offline_category) {
                            case 'scanCode':
                                $urlData = $this->getOfflineQrcode($type);
                                break;
                            case 'addFriend':
                                $urlData = $this->getAddFriendQrcode($type);
                                break;
                            case 'transfer':
                                $urlData = $this->getOneBankCard();
                                break;
                            default:
                                $urlData = '';
                                break;
                        }
                    }

                    $aChannels[$k]['channelList'][] = [
                        'id'            => $oPaymentChannel->id,
                        'display_name'  => $oPaymentChannel->display_name,
                        'position'      => [
                            'type'            => $oPaymentChannel->position, // 1：线上， 2：线下
                            'offLineCategory' => $oPaymentChannel->offline_category,
                            'data'            => $urlData,
                        ],
                        'deposit_range' => [
                            'isRange' => $isRange,
                            'data'    => $aDepositRange,
                        ],
                        'remark'        => $oPaymentChannel->remark,
                    ];
                }

            }
            $result['status'] = 0;
            $result['msg']    = 'success';
            $result['data']   = $aChannels;
        }
        return $response->withJson($result);

    }

    private function getOfflineQrcode($type)
    {
        $privateQrcode = PrivateQrcode::where('type', $type)->where('status', 1)->first();
        if (empty($privateQrcode)) {
            $privateQrcode = PrivateQrcode::where('type', $type)->where('status', 0)->orderBy('money', 'ASC')->orderBy('count', 'ASC')->first();
        }
        if ($privateQrcode) {
            return ['id' => $privateQrcode->id, 'type' => $type, 'url' => $privateQrcode->url];
        } else {
            return '未找到二维码';
        }

    }

    /**
     * @param $type     1,wechat  2,alipay
     * @return mixed
     */
    private function getAddFriendQrcode($type)
    {
        $src              = '';
        $oAddFriendQrcode = Picture::where('type', $type)->where('enabled', 1)->first();
        if (is_object($oAddFriendQrcode)) {
            $src = $oAddFriendQrcode->picture;
        }

        return $src;
    }

    private function getOneBankCard()
    {
        $result     = [];
        $oBankCards = BankCards::where('status', '1')->first();
        if (is_object($oBankCards)) {
            $result['id']          = $oBankCards->id;
            $result['bankName']    = $oBankCards->bank_name;
            $result['bankCard']    = $oBankCards->bank_number;
            $result['bankAccount'] = $oBankCards->user_name;
        }

        return $result;
    }

}
