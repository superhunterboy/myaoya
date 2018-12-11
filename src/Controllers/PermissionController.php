<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
// use \ReflectionClass;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\Permission;

class PermissionController extends BaseController
{
    /**
     * @api {get} /admin/getPermissions 权限
     * @apiName GetPermissions
     * @apiGroup Permission
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": [
     *           {
     *               "category": "其他",
     *               "permission": [
     *                   {
     *                       "id": 24,
     *                       "name": "支付失败的订单记录",
     *                       "require": 0
     *                   }
     *               ]
     *           },
     *           {
     *               "category": "支付记录",
     *               "permission": [
     *                   {
     *                       "id": 19,
     *                       "name": "查询入款列表",
     *                       "require": 0
     *                   },
     *                   {
     *                       "id": 20,
     *                       "name": "入款操作",
     *                       "require": 0
     *                   },
     *                   {
     *                       "id": 21,
     *                       "name": "获取未处理入款记录数量",
     *                       "require": 0
     *                   }
     *               ]
     *           }
     *       ]
     *   }
     */
    public function getPermissions(Request $request, Response $response, $args)
    {
        $categories = [
            0  => ['category' => '其他', 'permission' => []],
            1  => ['category' => '支付记录', 'permission' => []],
            2  => ['category' => '出款记录', 'permission' => []],
            3  => ['category' => '支付平台', 'permission' => []],
            4  => ['category' => '出款平台', 'permission' => []],
            5  => ['category' => '用户', 'permission' => []],
            6  => ['category' => '系统设置', 'permission' => []],
            7  => ['category' => '微信二维码', 'permission' => []],
            8  => ['category' => '加好友二维码', 'permission' => []],
            9  => ['category' => '业务平台', 'permission' => []],
            10 => ['category' => '登录', 'permission' => []],
        ];
        $permissions = Permission::all()->toArray();
        foreach ($permissions as $permission) {
            array_push($categories[$permission['category']]['permission'], [
                'id'      => $permission['id'],
                'name'    => $permission['name'],
                'require' => $permission['require'],
                'action'  => $permission['action'],
            ]);
        }
        return $response->withJson(['status' => 0, 'msg' => '', 'data' => $categories]);
    }

    public function updatePermissions(Request $request, Response $response, $args)
    {
        $routes = $this->router->getRoutes();
        foreach ($routes as $route) {
            $identifier = $route->getIdentifier();
            $callable   = basename($route->getCallable());
            $pattern    = $route->getPattern();
            $methods    = $route->getMethods();
            if ($callable && strpos($pattern, '/admin') !== false) {
                Permission::updateOrCreate([
                    'action' => $callable,
                ], [
                    // 'name'   => $identifier,
                    'method' => $methods[0],
                    'route'  => $pattern,
                    'action' => $callable,
                ]);
            }
        }
        // $path = __DIR__ . '/*Controller.php';
        // foreach (glob($path) as $file) {
        //     $class   = new ReflectionClass(__NAMESPACE__ . '\\' . basename($file, '.php'));
        //     $methods = $class->getMethods();
        //     foreach ($methods as $method) {
        //         echo $method->getDocComment();
        //     }
        // }
        return $response->withJson(['status' => 0, 'msg' => '', 'data' => []]);
    }
}
