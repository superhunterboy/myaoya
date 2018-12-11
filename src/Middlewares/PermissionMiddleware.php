<?php

namespace Weiming\Middlewares;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
// use \Slim\Exception\NotFoundException;
use \Weiming\Middlewares\BaseMiddleware;
use \Weiming\Models\Permission;

class PermissionMiddleware extends BaseMiddleware
{
    /**
     * 访问权限验证中间件
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $messages = [];
        $route    = $request->getAttribute('route');
        if (empty($route)) {
            return $response->withJson(['status' => 1, 'msg' => '404, Url address error.', 'data' => []]);
            // throw new NotFoundException($request, $response);
        }
        $pattern       = $route->getPattern();
        $routeCallable = str_replace("Weiming\\Controllers\\", '', $route->getCallable());
        $action        = explode(':', $routeCallable);
        if (strpos($pattern, '/admin') !== false && isset($action[1]) && $action[1] !== 'login') {
            $permissions = explode(',', $this->jwt->userInfo->permissions);
            if (empty($permissions)) {
                return $response->withJson(['status' => 1, 'msg' => '无权限访问，请分配权限', 'data' => []]);
            }
            $permission = Permission::whereRaw("`action` = '{$routeCallable}'")->first();
            if (empty($permission)) {
                return $response->withJson(['status' => 1, 'msg' => '权限' . $routeCallable . '未加入权限列表中，请联系开发人员', 'data' => []]);
            }
            if (!in_array($permission->id, $permissions)) {
                return $response->withJson(['status' => 1, 'msg' => '无权限访问，请分配权限', 'data' => []]);
            }
        }
        return $next($request, $response);
    }
}
