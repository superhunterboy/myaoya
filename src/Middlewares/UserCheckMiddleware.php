<?php

namespace Weiming\Middlewares;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Exception\NotFoundException;
use \Weiming\Libs\Utils;
use \Weiming\Middlewares\BaseMiddleware;
use \Weiming\Models\User;

class UserCheckMiddleware extends BaseMiddleware
{
    /**
     * 用户登录状态检查中间件
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $route = $request->getAttribute('route');
        if (empty($route)) {
            throw new NotFoundException($request, $response);
        }
        $pattern       = $route->getPattern();
        $routeCallable = str_replace("Weiming\\Controllers\\", '', $route->getCallable());
        $action        = explode(':', $routeCallable);
        if (strpos($pattern, '/admin') !== false && isset($action[1]) && $action[1] !== 'login') {
            // 登录之后才检测
            $ip        = Utils::getIp();
            $userInfo  = $this->jwt->userInfo;
            $loginIp   = $userInfo->ip;
            $lastlogin = $userInfo->lastlogin;
            $user      = User::select(['ip', 'lastlogin'])->where('id', '=', $userInfo->id)->first();
            // var_dump($ip, $loginIp, $lastlogin);
            if ($user && ($user->ip != $loginIp || $user->lastlogin != $lastlogin)) {
                $this->clearCookies();
                return $response->withJson([
                    'status' => 1,
                    'msg'    => '您的账号在其他地方登录，请确保一个账号只能一人使用',
                    'data'   => [],
                ], 401);
            } elseif ($ip != $loginIp) {
                $this->clearCookies();
                return $response->withJson([
                    'status' => 1,
                    'msg'    => '您上网的IP地址发生了变化，请重新登录',
                    'data'   => [],
                ], 401);
            }
        }
        return $next($request, $response);
    }

    /**
     * 强制清除用户浏览器 token cookie
     */
    private function clearCookies()
    {
        if (isset($_COOKIE['token']) && $_COOKIE['token']) {
            setcookie('token', '', time() - 3600 * 24, '/');
        }
    }
}
