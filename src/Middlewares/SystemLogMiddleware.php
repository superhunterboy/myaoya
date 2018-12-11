<?php

namespace Weiming\Middlewares;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Exception\NotFoundException;
use \Weiming\Middlewares\BaseMiddleware;
use \Weiming\Libs\Utils;

class SystemLogMiddleware extends BaseMiddleware
{
    /**
     * 系统日志中间件
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
        //记录所有日志 不再过滤admin路由
        //if (strpos($pattern, '/admin') !== false) {
            $datas = file_get_contents('php://input');
            parse_str($datas, $params);
            $user = (object) [
                'id'        => '',
                'username'  => $params['username'] ?? '',
                'lastlogin' => date('Y-m-d H:i:s'),
                'ip'        => Utils::getIp(),
            ];
            if (isset($action[1]) && $action[1] !== 'login' && !empty($this->jwt->userInfo->id)) {
                // 登录之后才能有值
                $user = $this->jwt->userInfo;
            }
            //$datas = file_get_contents('php://input');
            //parse_str($datas, $params);
            // 防止日志中泄露密码
            if (isset($params['password'])) {
                unset($params['password']);
            }
            if (isset($params['old_password'])) {
                unset($params['old_password']);
            }
            if (isset($params['new_password'])) {
                unset($params['new_password']);
            }
            if ($action[1] != 'notOperatOrders') {
                $this->logger->addInfo('System operation logs:', [
                    'route' => $routeCallable,
                    'datas' => $params,
                    'user'  => [
                        'id'        => $user->id,
                        'username'  => $user->username,
                        'lastlogin' => $user->lastlogin,
                        'ip'        => $user->ip,
                    ],
                ]);
            }
        //}
            return $next($request, $response);
    }
}
