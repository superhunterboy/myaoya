<?php

namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\SMServer;
use Weiming\Models\Code;

class SmsController extends BaseController
{
    /**
     * @api {get} /admin/isSmsVerify 短信验证是否开启
     * @apiName IsSmsVerify
     * @apiGroup Code
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "短信验证功能已开启",
     *       "data": []
     *   }
     */
    public function isSmsVerify(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '短信验证功能已关闭', 'data' => []];
        $smsInfo = $this->settings->get('sms');
        $isOpen  = $smsInfo['isOpen'] ?? false;
        if ($isOpen) {
            $result['status'] = 0;
            $result['msg']    = '短信验证功能已开启';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/sendCode 发送验证码
     * @apiName SendCode
     * @apiGroup Code
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "发送成功",
     *       "data": {
     *           "code": 200,    // 第三方接口api返回的状态码
     *           "msg": "211",   // 第三方接口api返回的发送id
     *           "obj": "8263"  // 验证码
     *       }
     *   }
     */
    public function sendCode(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '发送失败', 'data' => []];
        $smsInfo  = $this->settings->get('sms');
        $mobile   = $smsInfo['mobile'] ?? '';
        $smserver = new SMServer($smsInfo['appKey'], $smsInfo['appSecret'], 'curl');
        $resData  = $smserver->sendSMSCode($smsInfo['templateId'], $mobile, 4);
        if ($resData && $resData['code'] == 200) {
            if (Code::create(['code' => $resData['obj'], 'send_id' => $resData['msg'], 'status' => $resData['code'], 'mobile' => $mobile])) {
                // unset($resData['msg']);
                unset($resData['obj']);
                $result = ['status' => 0, 'msg' => '发送成功', 'data' => $resData];
            }
        } else {
            $result['msg']  = '发送失败，原因：' . $resData['msg'];
            $result['data'] = $resData;
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/verifyCode?code=xxxx 校验验证码
     * @apiName VerifyCode
     * @apiGroup Code
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} code 验证码
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "验证成功",
     *       "data": {
     *           "code": 200
     *       }
     *   }
     */
    public function verifyCode(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '验证失败', 'data' => []];
        $getDatas = $request->getQueryParams();
        $code     = $getDatas['code'] ?? '';
        if ($code) {
            $smsInfo  = $this->settings->get('sms');
            $code     = trim($code);
            $mobile   = $smsInfo['mobile'] ?? '';
            $smserver = new SMServer($smsInfo['appKey'], $smsInfo['appSecret'], 'curl');
            $resData  = $smserver->sendSMSVerifycode($mobile, $code);
            if ($resData && $resData['code'] == 200) {
                if (Code::where('mobile', '=', $mobile)->where('code', '=', $code)->update(['ok' => 1])) {
                    $result = ['status' => 0, 'msg' => '验证成功', 'data' => $resData];
                }
            } else {
                $result['data'] = $resData;
            }
        }
        return $response->withJson($result);
    }
}
