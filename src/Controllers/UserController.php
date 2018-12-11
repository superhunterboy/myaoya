<?php

namespace Weiming\Controllers;

use \Google\Authenticator\GoogleAuthenticator;
use \Google\Authenticator\GoogleQrUrl;
use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Utils;
use \Weiming\Models\Pay;
use \Weiming\Models\User;

class UserController extends BaseController
{
    /**
     * 添加用户
     */
    public function addUser(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '', 'data' => []];
        $postDatas = $request->getParsedBody();
        if (empty($postDatas['username']) || empty($postDatas['password'])) {
            $result['msg'] = '用户名或密码不能为空';
        } else {
            // 验证用户名唯一性
            $user = User::where('username', '=', $postDatas['username'])->first();
            if (empty($user)) {
                if (isset($postDatas['password'])) {
                    $postDatas['password'] = md5(sha1($postDatas['password']));
                }
                $result['status'] = 0;
                $result['msg']    = '用户创建成功';
                $result['data']   = User::create($postDatas);
            } else {
                $result['msg'] = '用户名已存在，请重新输入';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 删除用户
     */
    public function deleteUser(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];
        $ids    = $request->getAttribute('id');
        if ($ids) {
            $ids        = Utils::getIds($ids);
            $notDelPays = Pay::whereIn('rk_user_id', $ids)->get()->toArray();
            if ($notDelPays) {
                $tmpArr = [];
                foreach ($notDelPays as $key => $val) {
                    array_push($tmpArr, $val['order_no']);
                }
                $result['msg'] = '为了数据的完整性，您删除的用户操作过以下支付账单[' . implode(',', $tmpArr) . ']';
            } else {
                $res = User::destroy($ids);
                if ($res) {
                    $result['status'] = 0;
                    $result['msg']    = '删除成功';
                } else {
                    $result['msg'] = '删除失败';
                }
            }
        } else {
            $result['msg'] = '传入参数错误';
        }
        return $response->withJson($result);
    }

    /**
     * 禁用，启用用户
     */
    public function endisableUser(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '', 'data' => []];
        $putDatas = $request->getParsedBody();
        if ($args['id'] > 0 && ($putDatas['status'] == 0 || $putDatas['status'] == 1)) {
            $user         = User::find($args['id']);
            $user->status = $putDatas['status'];
            $res          = $user->save();
            if ($res) {
                $result['status'] = 0;
                $result['msg']    = '操作成功';
            }
        } else {
            $result['msg'] = '参数错误';
        }
        return $response->withJson($result);
    }

    /**
     * 更新用户
     */
    public function updateUser(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '', 'data' => []];
        if ($args['id'] > 0) {
            $putDatas = $request->getParsedBody();
            if (empty($putDatas['username'])) {
                $result['msg'] = '用户名不能为空';
            } else {
                // 验证用户名唯一性
                $user = User::where('username', '=', $putDatas['username'])->where('id', '!=', $args['id'])->first();
                if (empty($user)) {
                    // if (isset($putDatas['password'])) {
                    //     $putDatas['password'] = md5(sha1($putDatas['password']));
                    // }
                    $user              = User::find($args['id']);
                    $user->username    = $putDatas['username'];
                    $user->password    = $user->password;
                    $user->realname    = $putDatas['realname'];
                    $user->type        = $putDatas['type']; //$user->type;
                    $user->permissions = $putDatas['permissions'] ?? '';
                    $user->company_ids = $putDatas['company_ids'];
                    $user->status      = $user->status;
                    $res               = $user->save();
                    if ($res) {
                        $result['status'] = 0;
                        $result['msg']    = '用户修改成功';
                    }
                } else {
                    $result['msg'] = '用户名已存在，请重新输入';
                }
            }
        } else {
            $result['msg'] = '参数错误';
        }
        return $response->withJson($result);
    }

    /**
     * 获取登录用户信息
     */
    public function getCurrentUser(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '参数错误', 'data' => []];
        $userId = $this->jwt->userInfo->id;
        if ($userId) {
            $res = User::find($userId);
            if ($res) {
                $result['status'] = 0;
                $result['msg']    = '获取用户信息OK';
                $result['data']   = $res->toArray();
            } else {
                $result['msg'] = '获取用户信息失败';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 查询用户
     */
    public function queryUsers(Request $request, Response $response, $args)
    {
        $result   = [];
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 10;
        // 数据权限
        $companyIds      = $this->jwt->userInfo->company_ids;
        $currentUserType = $this->jwt->userInfo->type;
        Paginator::currentPathResolver(function () {
            return "/admin/users";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        // 管理员看全部业务平台
        if ($currentUserType == 1) {
            $result = User::orderby('status', 'desc')->orderby('updated_at', 'desc')->paginate($perPage);
        } else {
            $result = User::orderby('status', 'desc')->orderby('updated_at', 'desc')->whereRaw('`type` = 1' . Utils::getRawSql($companyIds))->paginate($perPage);
        }
        return $response->withJson($result);
    }

    /**
     * 修改密码
     */
    public function modifyPassword(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '', 'data' => []];
        $putDatas = $request->getParsedBody();
        if ($putDatas['old_password'] && $putDatas['new_password']) {
            $userId = $this->jwt->userInfo->id;
            if ($userId) {
                $user           = User::find($userId);
                $verifyPassword = md5(sha1($putDatas['old_password']));
                if ($verifyPassword != $user->password) {
                    $result['msg'] = '旧密码错误，您不能修改密码';
                } else {
                    $user->password = md5(sha1($putDatas['new_password']));
                    $res            = $user->save();
                    if ($res) {
                        $result['status'] = 0;
                        $result['msg']    = '密码修改成功';
                    }
                }
            }
        } else {
            $result['msg'] = '参数错误';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/resetPassword/:id 重置密码
     * @apiName ResetPassword
     * @apiGroup User
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 用户ID
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "密码重置成功",
     *       "data": 000000
     *   }
     */
    public function resetPassword(Request $request, Response $response, $args)
    {
        $result   = ['status' => 1, 'msg' => '', 'data' => []];
        $userType = $this->jwt->userInfo->type;
        $userId   = $args['id'] ?? 0;
        if ($userType == 1) {
            $user           = User::find($userId);
            $user->password = md5(sha1('000000')); //密码重置为六个零
            $mark           = $user->save();
            if (empty($mark)) {
                $result['msg'] = '密码重置失败';
            } else {
                $result['status'] = 0;
                $result['msg']    = '密码重置成功';
                $result['data']   = '000000';
            }
        } else {
            $result['msg'] = '没有权限';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/bindOneTimePwd 扫码绑定OTP验证器
     * @apiName BindOneTimePwd
     * @apiGroup User
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "请使用GoogleAuthenticator App扫描二维码，输入OTP进行绑定",
     *       "data": {
     *           "qrcode": "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FAoYaFinancialSystem%3Aadmin%3Fsecret%3D5WSRSVWYCIB6IAFO%26issuer%3DAoYaFinancialSystem"
     *       }
     *   }
     */
    public function bindOneTimePwd(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '获取二维码失败', 'data' => []];
        $g      = new GoogleAuthenticator();
        $secret = $g->generateSecret();
        if ($secret) {
            $qrcode = GoogleQrUrl::generate('admin', $secret, 'AoYaFinancialSystem');
            if ($qrcode) {
                if (User::where('username', '=', 'admin')->where('type', '=', 1)->where('is_bind', '=', 0)->update(['secret' => $secret])) {
                    $result['status'] = 0;
                    $result['msg']    = '请使用GoogleAuthenticator App扫描二维码，输入OTP进行绑定';
                    $result['data']   = [
                        'qrcode' => $qrcode,
                    ];
                } else {
                    $result['msg'] = '已经绑定OTP验证器';
                }
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/verifyBindOneTimePwd 绑定验证OTP
     * @apiName VerifyBindOneTimePwd
     * @apiGroup User
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} code OTP密码
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "OTP验证成功，已绑定OTP验证器，你可以解绑，然后重新绑定",
     *       "data": []
     *   }
     */
    public function verifyBindOneTimePwd(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '', 'data' => []];
        $postDatas = $request->getParsedBody();
        $code      = $postDatas['code'] ?? '';
        if ($code) {
            $g    = new GoogleAuthenticator();
            $user = User::where('username', '=', 'admin')->where('type', '=', 1)->where('is_bind', '=', 0)->first();
            if ($user) {
                $secret = $user->secret;
                if ($g->checkCode($secret, $code)) {
                    $user->is_bind = 1;
                    if ($user->save()) {
                        $result['status'] = 0;
                        $result['msg']    = 'OTP验证成功，已绑定OTP验证器，你可以解绑，然后重新绑定';
                    }
                } else {
                    $result['msg'] = 'OTP验证失败';
                }
            } else {
                $result['msg'] = '已经绑定OTP验证器，请先解绑';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/verifyUnbindOneTimePwd 解绑验证OTP
     * @apiName VerifyUnbindOneTimePwd
     * @apiGroup User
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} code OTP密码
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "OTP验证成功，已解绑OTP验证器，你可以重新绑定",
     *       "data": []
     *   }
     */
    public function verifyUnbindOneTimePwd(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '', 'data' => []];
        $postDatas = $request->getParsedBody();
        $code      = $postDatas['code'] ?? '';
        if ($code) {
            $g    = new GoogleAuthenticator();
            $user = User::where('username', '=', 'admin')->where('type', '=', 1)->where('is_bind', '=', 1)->first();
            if ($user) {
                $secret = $user->secret;
                if ($g->checkCode($secret, $code)) {
                    $user->secret  = '';
                    $user->is_bind = 0;
                    if ($user->save()) {
                        $result['status'] = 0;
                        $result['msg']    = 'OTP验证成功，已解绑OTP验证器，你可以重新绑定';
                    }
                } else {
                    $result['msg'] = 'OTP验证失败';
                }
            } else {
                $result['msg'] = '已经解绑OTP验证器，请先绑定';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /isEnableOtp OTP验证是否绑定
     * @apiName IsEnableOtp
     * @apiGroup User
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "获取状态成功",
     *       "data": {
     *           "isBind": 0 // 0为未绑定，1为已绑定；登录表单可以根据此接口来判断是否显示OTP输入框
     *       }
     *   }
     */
    public function isEnableOtp(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '获取状态失败', 'data' => []];
        $admin  = User::select(['is_bind'])->where('username', '=', 'admin')->where('type', '=', 1)->first();
        if ($admin) {
            $result['status'] = 0;
            $result['msg']    = '获取状态成功';
            $result['data']   = [
                'isBind' => $admin->is_bind,
            ];
        }
        return $response->withJson($result);
    }
}
