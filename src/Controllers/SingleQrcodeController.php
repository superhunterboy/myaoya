<?php

namespace Weiming\Controllers;

use Illuminate\Pagination\Paginator;
use OSS\Core\OssException;
use OSS\OssClient;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Payments\PingAn;
use Weiming\Libs\Utils;
use Weiming\Models\Merchant;
use Weiming\Models\PriQrcodePay;
use Weiming\Models\PrivateQrcode;
use Weiming\Models\Qrcode;
use Weiming\Models\Setting;

class SingleQrcodeController extends BaseController
{
    /**
     * @api {get} /getSingleQQ 获取QQ收款二维码
     * @apiName GetSingleQQ
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   { //个人二维码
     *       "status": 0,
     *       "msg": "",
     *       "data": {
     *           "id": 4,
     *           "type": "3",
     *           "url": "http://wm2028.oss-cn-hangzhou.aliyuncs.com/755559a5053ff9bb5d458b7cab707faa.jpg"
     *       }
     *   }
     */
    public function getSingleQQ(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '请联系客服，更新QQ收款二维码，谢谢！', 'data' => []];
        $wechat = Setting::where('key', 'qq_single_qrcode')->first(); // QQ支付：1 个人二维码
        $type   = 0;
        if (!empty($wechat) && in_array($wechat->val, [1])) {
            $type = $wechat->val;
        }
        if ($type == 1) {
            $privateQrcode = PrivateQrcode::where('type', '3')->where('status', '1')->first(); // orderBy('money', 'ASC')->orderBy('count', 'ASC')->
            if (empty($privateQrcode)) {
                $privateQrcode = PrivateQrcode::where('type', '3')->where('status', '!=', '2')->orderBy('money', 'ASC')->orderBy('count', 'ASC')->first();
            }
            if ($privateQrcode) {
                $result['status']       = 0;
                $result['msg']          = '';
                $result['data']['id']   = $privateQrcode->id;
                $result['data']['type'] = $privateQrcode->type;
                $result['data']['url']  = $privateQrcode->url;
            }
        }
        return $response->withJson($result);
    }

    public function getSingleYUN(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '请联系客服，更新云闪付收款二维码，谢谢！', 'data' => []];
        $wechat = Setting::where('key', 'qq_single_qrcode')->first(); // QQ支付：1 个人二维码
        $type   = 0;
        if (!empty($wechat) && in_array($wechat->val, [1])) {
            $type = $wechat->val;
        }
        if ($type == 1) {
            $privateQrcode = PrivateQrcode::where('type', '4')->where('status', '1')->first(); // orderBy('money', 'ASC')->orderBy('count', 'ASC')->
            if (empty($privateQrcode)) {
                $privateQrcode = PrivateQrcode::where('type', '4')->where('status', '!=', '2')->orderBy('money', 'ASC')->orderBy('count', 'ASC')->first();
            }
            if ($privateQrcode) {
                $result['status']       = 0;
                $result['msg']          = '';
                $result['data']['id']   = $privateQrcode->id;
                $result['data']['type'] = $privateQrcode->type;
                $result['data']['url']  = $privateQrcode->url;
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /getSingleWechat 获取微信收款二维码
     * @apiName GetSingleWechat
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.1
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   { //个人二维码
     *       "status": 0,
     *       "msg": "",
     *       "data": {
     *           "id": 4,
     *           "type": "1",
     *           "url": "http://wm2028.oss-cn-hangzhou.aliyuncs.com/755559a5053ff9bb5d458b7cab707faa.jpg"
     *       }
     *   }
     *   { //商家二维码
     *       "status": 0,
     *       "msg": "获取成功",
     *       "data": {
     *       "url": "https://q.orangebank.com.cn/?O=b7a9a0e67b8f67ba09bb96148035bb58",
     *       "type": "2",
     *       "merId": 2
     *   }
     */
    public function getSingleWechat(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '请上传二维码', 'data' => []];
        $wechat = Setting::where('key', 'wechat_single_qrcode')->first(); //微信支付 1:个人二维码 2:商家二维码
        if (!empty($wechat) && in_array($wechat->val, [1, 2, 3])) {
            $type = $wechat->val;
        }

        if (!empty($type)) {
            if ($type == '1') {
                //&& in_array($payType, ['1', '2'])
                $privateQrcode = PrivateQrcode::where('type', '1')->where('status', '1')->first(); //orderBy('money', 'ASC')->orderBy('count', 'ASC')->
                if (empty($privateQrcode)) {
                    $privateQrcode = PrivateQrcode::where('type', '1')->where('status', '!=', '2')->orderBy('money', 'ASC')->orderBy('count', 'ASC')->first();
                }
                if ($privateQrcode) {
                    $result['status']       = 0;
                    $result['msg']          = '';
                    $result['data']['id']   = $privateQrcode->id;
                    $result['data']['type'] = $privateQrcode->type;
                    $result['data']['url']  = $privateQrcode->url;
                } else {
                    $result['msg'] = '未找到二维码,请上传二维码!';
                }
            } elseif ($type == '2') {
                //&& in_array($payType, ['1', '2'])
                $merchantQrcode = Merchant::whereRaw('type like "%1%"')->where('status', '1')->first();
                if (!$merchantQrcode) {
                    $merchantQrcode = Merchant::whereRaw('type like "%1%"')->first();
                }
                if ($merchantQrcode) {
                    if (!empty($merchantQrcode->key)) {
                        $conf = json_decode($merchantQrcode->key, true);
                    }
                    if (!empty($merchantQrcode->open_id)) {
                        $conf['open_id'] = $merchantQrcode->open_id;
                    }
                    if (!empty($merchantQrcode->open_key)) {
                        $conf['open_key'] = $merchantQrcode->open_key;
                    }
                    $conf['shop_no'] = $merchantQrcode->shop_no;
                    if (empty($conf['shop_no'])) {
                        $result['msg'] = '门店编号未设定';
                        return $response->withJson($result);
                    }

                    $pingAnObj = Pingan::getInstance($conf);
                    $res       = $pingAnObj->signature('1')->payment();
                    $resArr    = json_decode((string) $res, true);
                    if (is_array($resArr) && $resArr['errcode'] == 0) {
                        $boolean = $pingAnObj->verifySign($resArr);

                        if ($boolean) {
                            $data    = $pingAnObj->data_decode($resArr['data']);
                            $openArr = json_decode($data, true);

                            if (is_array($openArr) && isset($openArr['open_id'])) {
                                $result['status']        = 0;
                                $result['msg']           = '获取成功';
                                $result['data']['url']   = 'https://q.orangebank.com.cn/?O=' . $openArr['open_id'];
                                $result['data']['type']  = '2';
                                $result['data']['merId'] = $merchantQrcode->id;

                                return $response->withJson($result); //https://q.orangebank.com.cn/?O=openid 商家二维码
                            } else {
                                $result['msg'] = '返回数据内容异常';
                            }

                        } else {
                            $result['msg'] = '返回数据签名错误';
                        }

                    } else {
                        //$res = (string)$res;
                        $res           = '商家二维码暂时不可用!';
                        $result['msg'] = $res;
                    }

                }

            } elseif ($type == 3) {
                // 优先选择每天剩余金额最大的二维码
                $qrcode = Qrcode::selectRaw("`id`, `type`, `url`, `limit` - `day_money` AS m")->where('disable', '=', 0)->orderBy('m', 'DESC')->first();
                if ($qrcode) {
                    $qrcode['type']   = 3; // PS: 不要奇怪，固定为 3 了
                    $result['status'] = $qrcode['m'] > 0 ? 0 : 1; // 无可用二维码就显示加好友二维码
                    $result['msg']    = '';
                    $result['data']   = $qrcode;
                }
            } else {
                $result['msg'] = '不支持的支付类型';
            }

        } else {
            $result['msg'] = '还未设置支付类型!';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /getSingleAlipay/:type 获取支付宝收款二维码
     * @apiName GetSingleAlipay
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.1
     * @apiPermission none
     *
     * @apiParam {Number} type 支付宝支付类型 2:个人 3:商家
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   { //个人
     *       "status": 0,
     *       "msg": "",
     *       "data": {
     *           "id": 2,
     *           "type": "2",
     *           "url": "https://wm2028.oss-cn-hangzhou.aliyuncs.com/0098cc805442dadb5b361d0a40b08938.png"
     *       }
     *   }
     *   { //商家
     *       "status": 0,
     *       "msg": "获取成功",
     *       "data": "https://q.orangebank.com.cn/?O=e9e560248f9ecab45dbe8a5fa7f7bebc"
     *       "merId": 1
     *   }
     */
    public function getSingleAlipay(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '请上传二维码', 'data' => []];
        $type   = $args['type'] ?? '';

        $alipay = Setting::where('key', 'alipay_single_qrcode')->first(); //支付宝 2:个人二维码 3:商家二维码
        if (!$alipay) {
            $result['msg'] = '支付宝支付设置错误';
            return $response->withJson($result);
        }
        $alipayState = explode(',', $alipay->val);
        if (!in_array($type, $alipayState)) {
            $result['msg'] = '不支持或未开启的支付类型';
            return $response->withJson($result);
        }

        if (!empty($type)) {
            if ($type == '2') {
                //&& in_array($payType, ['1', '2'])
                $privateQrcode = PrivateQrcode::where('type', '2')->where('status', '1')->first(); //orderBy('money', 'ASC')->orderBy('count', 'ASC')->
                if (empty($privateQrcode)) {
                    $privateQrcode = PrivateQrcode::where('type', '2')->where('status', '!=', '2')->orderBy('money', 'ASC')->orderBy('count', 'ASC')->first(); //
                }
                if ($privateQrcode) {
                    $result['status']       = 0;
                    $result['msg']          = '';
                    $result['data']['id']   = $privateQrcode->id;
                    $result['data']['type'] = $privateQrcode->type;
                    $result['data']['url']  = $privateQrcode->url;
                } else {
                    $result['msg'] = '未找到二维码,请上传二维码!';
                }
            } elseif ($type == '3') {
                //&& in_array($payType, ['1', '2'])
                $merchantQrcode = Merchant::whereRaw('type like "%2%"')->where('status', '1')->first();
                if (!$merchantQrcode) {
                    $merchantQrcode = Merchant::whereRaw('type like "%2%"')->first();
                }
                if ($merchantQrcode) {
                    if (!empty($merchantQrcode->key)) {
                        $conf = json_decode($merchantQrcode->key, true);
                    }
                    if (!empty($merchantQrcode->open_id)) {
                        $conf['open_id'] = $merchantQrcode->open_id;
                    }
                    if (!empty($merchantQrcode->open_key)) {
                        $conf['open_key'] = $merchantQrcode->open_key;
                    }
                    $conf['shop_no'] = $merchantQrcode->shop_no; //var_dump($conf); exit;
                    if (empty($conf['shop_no'])) {
                        $result['msg'] = '门店编号未设定';
                        return $response->withJson($result);
                    }

                    $pingAnObj = Pingan::getInstance($conf);
                    $res       = $pingAnObj->signature('1')->payment();
                    $resArr    = json_decode((string) $res, true);
                    if (is_array($resArr) && $resArr['errcode'] == 0) {
                        $boolean = $pingAnObj->verifySign($resArr);

                        if ($boolean) {
                            $data    = $pingAnObj->data_decode($resArr['data']);
                            $openArr = json_decode($data, true);

                            if (is_array($openArr) && isset($openArr['open_id'])) {
                                $result['status']        = 0;
                                $result['msg']           = '获取成功';
                                $result['data']['url']   = 'https://q.orangebank.com.cn/?O=' . $openArr['open_id'];
                                $result['data']['merId'] = $merchantQrcode->id;

                                return $response->withJson($result); //https://q.orangebank.com.cn/?O=openid 商家二维码
                            } else {
                                $result['msg'] = '返回数据内容异常';
                            }

                        } else {
                            $result['msg'] = '返回数据签名错误';
                        }

                    } else {
                        //$res = (string)$res;
                        $res           = '商家二维码暂时不可用!';
                        $result['msg'] = $res;
                    }

                }

            } else {
                $result['msg'] = '还未设置支付类型!';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/addSingleQrcode 添加个人二维码
     * @apiName AddSingleQrcode
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} account 微信号或者支付宝账号
     * @apiParam {String} content 图片 Base64 格式内容，如：data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkS ...
     * @apiParam {Number} type 二维码类型 1:微信 2:支付宝
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功",
     *       "data": []
     *   }
     */
    public function addSingleQrcode(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '添加失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $account   = $postDatas['account'] ?? '';
        $content   = $postDatas['content'] ?? '';
        $type      = $postDatas['type'] ?? 1;
        if (!empty($content)) {
            $qrcodeInfo = Utils::parseBase64Image($content);
            if ($qrcodeInfo) {
                $qrcode = PrivateQrcode::where('qrcode_name', $account)->where('type', $type)->first();
                if ($qrcode) {
                    $result['msg'] = '二维码账户重复';
                    return $response->withJson($result);
                }
                $res             = null;
                $accessKeyId     = $this->settings['oss']['accessKeyId'];
                $accessKeySecret = $this->settings['oss']['accessKeySecret'];
                $endpoint        = $this->settings['oss']['endpoint'];
                $bucket          = $this->settings['oss']['bucket'];
                $object          = md5(uniqid('', true)) . '.' . $qrcodeInfo['ext'];
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $res       = $ossClient->putObject($bucket, $object, base64_decode($qrcodeInfo['content']));
                } catch (OssException $e) {
                    $result['msg'] = $e->getMessage();
                    return $response->withJson($result);
                }
                $qrcodeUrl = $res['oss-request-url'] ?? ($res['info']['url'] ?? null);
                if ($qrcodeUrl) {
                    if (PrivateQrcode::create([
                        'qrcode_name' => $account,
                        'url'         => str_replace('http://', 'https://', $qrcodeUrl),
                        'type'        => $type,
                        'status'      => '0',
                    ])) {
                        $result['status'] = 0;
                        $result['msg']    = '添加成功';
                    }
                }
            } else {
                $result['msg'] = 'Base64格式错误!';
            }
        } else {
            $result['msg'] = '二维码不能为空';
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateSingleQrcode/:id 更新个人二维码
     * @apiName UpdateSingleQrcode
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码id
     * @apiParam {String} account 二维码账户
     * @apiParam {String} content 图片 Base64 格式内容，如：data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkS ...
     * @apiParam {Number} type 二维码类型 1:微信 2:支付宝 3:QQ
     * @apiParam {Numver} status 二维码状态 0:取消锁定 1:锁定 2:屏蔽
     * @apiParam {String} msg 备注
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function updateSingleQrcode(Request $request, Response $response, $args)
    {
        $result      = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id          = $args['id'];
        $postDatas   = $request->getParsedBody();
        $qrcode_name = $postDatas['account'] ?? '';
        $content     = $postDatas['content'] ?? '';
        $type        = $postDatas['type'] ?? '';
        $status      = $postDatas['status'] ?? '';
        $msg         = $postDatas['msg'] ?? '';

        if (empty($type)) {
            $result['msg'] = '二维码类型不能为空';

        }
        if (!empty($content)) {
            $qrcodeInfo = Utils::parseBase64Image($content);
            if ($qrcodeInfo) {
                $res             = null;
                $accessKeyId     = $this->settings['oss']['accessKeyId'];
                $accessKeySecret = $this->settings['oss']['accessKeySecret'];
                $endpoint        = $this->settings['oss']['endpoint'];
                $bucket          = $this->settings['oss']['bucket'];
                $object          = md5(uniqid('', true)) . '.' . $qrcodeInfo['ext'];
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $res       = $ossClient->putObject($bucket, $object, base64_decode($qrcodeInfo['content']));
                } catch (OssException $e) {
                    $result['msg'] = $e->getMessage();
                    return $response->withJson($result);
                }
                $qrcodeUrl = $res['oss-request-url'] ?? ($res['info']['url'] ?? null);
                if ($qrcodeUrl) {
                    $data['url'] = str_replace('http://', 'https://', $qrcodeUrl);
                }
            } else {
                $result['msg'] = 'Base64格式错误!';
                $target        = true;
            }
        }
        if (isset($target)) {
            return $response->withJson($result);
        }
        $data['msg'] = ($msg == 'null' ? '' : $msg);
        if ($qrcode_name != '') {
            $count      = PriQrcodePay::where('qrcode_id', '=', $id)->count();
            $modifyMame = PrivateQrcode::where('id', $id)->where('qrcode_name', $qrcode_name)->first();
            if ($count > 0) {
                if ($modifyMame) {
                    $data['qrcode_name'] = $qrcode_name;
                } else {
                    $result['msg'] = '二维码有收款记录，不能修改收款人';
                    return $response->withJson($result);
                }
            } else {
                $data['qrcode_name'] = $qrcode_name;
            }
        }

        if ($status != '' && in_array($status, ['0', '1', '2'])) {
            if ($status == '1' && in_array($type, ['1', '2', '3'])) {
                $priInfo = PrivateQrcode::where('status', '1')->where('type', $type);

                $priInfo->where('type', $type)->update(['status' => '0']);
            }
            $data['status'] = $status;
        }
        if (PriQrcodePay::where('qrcode_id', '=', $id)->count() == 0) {
            if (PrivateQrcode::where('id', '=', $id)->update($data)) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
            }
        } else {
            if (PrivateQrcode::where('id', '=', $id)->update(['msg' => $msg, 'status' => $status])) {
                $result['status'] = 0;
                $result['msg']    = '更新成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/singleQrcodes/:type 个人二维码列表
     * @apiName SingleQrcodes
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} type 二维码类型 1:微信 2:支付宝
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     * {
     *     "current_page": 1,
     *     "data": [
     *         {
     *             "id": 1,
     *             "qrcode_name": "ran",
     *             "url": "https://wm2028.oss-cn-hangzhou.aliyuncs.com/5e330c956ed8c30c4354c8b8628fb790.png",
     *             "money": 0,
     *             "count": 0,
     *             "type": "1",
     *             "status": "0",
     *             "msg": "",
     *             "created_at": "2017-11-14 14:57:31",
     *             "updated_at": "2017-11-14 14:57:31"
     *         }
     *     ],
     *     "first_page_url": "/admin/singleQrcodes/1?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "/admin/singleQrcodes/1?page=1",
     *     "next_page_url": null,
     *     "path": "/admin/singleQrcodes",
     *     "per_page": 20,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     * }
     */
    public function singleQrcodes(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;
        $type     = $args['type'] ?? '';

        if (empty($type) || !in_array($type, [1, 2, 3 ,4 ])) {
            return $response->withJson(['status' => 1, 'msg' => '二维码类型错误!', 'data' => []]);
        }

        Paginator::currentPathResolver(function () use ($type) {
            return "/admin/singleQrcodes/{$type}";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $result = PrivateQrcode::whereRaw("`type` = $type")->orderBy('status', 'ASC'); //->where('status', '!=', '2')
        $result = $result->orderBy('id', 'DESC')->paginate($perPage);

        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deleteSingleQrcode/:id 删除个人二维码
     * @apiName DeleteSingleQrcode
     * @apiGroup SingleQrcode
     * @apiVersion 1.0.1
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码id 多个逗号分隔1,2,3,4...
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {//单个删除
     *       "status": 0,
     *       "msg": "删除成功",
     *       "data": []
     *   }
     *   {//多个删除
     *       "status": 1,
     *       "msg": "删除失败",
     *       "data": {
     *           "success": {
     *               "msg": "二维码:成功删除!",
     *               "count": 0
     *           },
     *           "error": {
     *               "msg": "二维码:3,4,下有支付记录,您不能删除",
     *               "count": 2
     *           }
     *       }
     *   }
     */
    public function deleteSingleQrcode(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $ids    = $args['id'] ?? false;
        $idsArr = explode(',', $ids);
//var_dump($ids);exit;
        if ($ids) {
            if (count($idsArr) > 1) {
                $success = ['msg' => '二维码:', 'count' => 0];
                $error   = ['msg' => '二维码:', 'count' => 0];
                foreach ($idsArr as $key => $value) {
                    if ($value == '') {
                        continue;
                    }
                    if (PriQrcodePay::where('qrcode_id', '=', $value)->count() == 0) {
                        if (PrivateQrcode::where('id', '=', $value)->delete()) {
                            $success['msg'] .= $value . ',';
                            $success['count'] += 1;
                        }
                    } else {
                        $error['msg'] .= $value . ',';
                        $error['count'] += 1;

                    }
                }
                $success['msg'] .= '成功删除!';
                $error['msg'] .= '下有支付记录,您不能删除';

                if ($success['count'] > 1) {
                    $result['status']          = 0;
                    $result['msg']             = '成功';
                    $result['data']['success'] = $success;
                    $result['data']['error']   = $error;
                } else {
                    $result['data']['success'] = $success;
                    $result['data']['error']   = $error;
                }

            } else {
                if (PriQrcodePay::where('qrcode_id', '=', $ids)->count() == 0) {
                    if (PrivateQrcode::where('id', '=', $ids)->delete()) {
                        $result['status'] = 0;
                        $result['msg']    = '删除成功';
                    }
                } else {
                    $result['msg'] = '该二维码下有支付记录，您不能删除';
                }
            }
        } else {
            $result['msg'] = '缺少参数!';
        }
        return $response->withJson($result);
    }

}
