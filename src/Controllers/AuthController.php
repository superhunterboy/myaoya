<?php

namespace Weiming\Controllers;

use \DateTime;
use \Firebase\JWT\JWT;
use \Google\Authenticator\GoogleAuthenticator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Utils;
use \Weiming\Models\User;

class AuthController extends BaseController
{
    /**
     * 后台用户登录
     */
    public function login(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '登录失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $username  = $postDatas['username'] ?? '';
        $password  = $postDatas['password'] ?? '';
        $code      = $postDatas['code'] ?? '';
        if ($username && $password) {
            $password = md5(sha1($password));
            $user     = User::where('username', '=', $username)->where('status', '=', 1)->first();
            if ($user && $user->password === $password) {
                // OTP 验证
                $otpSecret = '';
                // 是否绑定OTP，绑定了就验证OTP，没有绑定就不验证
                $admin = User::where('username', '=', 'admin')->where('type', '=', 1)->where('is_bind', '=', 1)->first();
                if ($admin) {
                    if (empty($code)) {
                        $result['msg'] = '安全令密码不能为空';
                        return $response->withJson($result);
                    }
                    $otpSecret = $admin->secret;
                    $g         = new GoogleAuthenticator();
                    if (!$g->checkCode($otpSecret, $code)) {
                        $result['msg'] = '安全令密码错误';
                        return $response->withJson($result);
                    }
                }
                // 更新登录信息
                $lastlogin       = date('Y-m-d H:i:s', time());
                $ip              = Utils::getIp();
                $user->lastlogin = $lastlogin;
                $user->ip        = $ip;
                $user->save();
                // JWT 生成 Token，并保存用户信息到token中
                $secret  = "35a7102186059ae8a1557f1e9c90ca47075d7c4e";
                $now     = new DateTime();
                $future  = new DateTime("now +12 hours");
                $payload = [
                    "iat"      => $now->getTimeStamp(),
                    "exp"      => $future->getTimeStamp(),
                    "scope"    => ["post", "delete", "put", "get"],
                    'userInfo' => [
                        'id'          => $user->id,
                        'username'    => $user->username,
                        'realname'    => $user->realname,
                        'type'        => $user->type,
                        'permissions' => $user->permissions,
                        'company_ids' => $user->company_ids,
                        'lastlogin'   => $lastlogin,
                        'ip'          => $ip,
                    ],
                ];
                $token = JWT::encode($payload, $secret);
                setcookie('token', $token, time() + 3600 * 24, '/');
                $result = ['status' => 0, 'msg' => '登录成功', 'data' => ['token' => $token]];
            } else {
                $result['msg'] = '用户名或密码错误';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 后台用户退出
     */
    public function logout(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '退出失败', 'data' => []];
        if (isset($_COOKIE['token']) && $_COOKIE['token']) {
            setcookie('token', '', time() - 3600 * 24, '/');
            $result = ['status' => 0, 'msg' => '退出成功', 'data' => []];
        }
        return $response->withJson($result);
    }
}
