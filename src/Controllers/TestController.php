<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
// use \ReflectionClass;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\Pay;
// use \Weiming\Models\Permission;

class TestController extends BaseController
{
    public function testLogger(Request $request, Response $response, $args)
    {
        $decoded = $request->getAttribute("jwtoken");
        var_dump($decoded);
        var_dump($this->jwt);
        // $routes = $this->router->getRoutes();
        // foreach ($routes as $route) {
        //     $identifier = $route->getIdentifier();
        //     $callable   = $route->getCallable();
        //     $pattern    = $route->getPattern();
        //     $methods    = $route->getMethods();
        //     if (strpos($pattern, '/admin') !== false) {
        //         Permission::updateOrCreate(['action' => $callable], ['name' => $identifier, 'method' => $methods[0], 'route' => $pattern, 'action' => $callable]);
        //     }
        // }
        // $path = __DIR__ . '/*Controller.php';
        // foreach (glob($path) as $file) {
        //     $class   = new ReflectionClass(__NAMESPACE__ . '\\' . basename($file, '.php'));
        //     $methods = $class->getMethods();
        //     foreach ($methods as $method) {
        //         echo $method->getDocComment();
        //     }
        // }
        // return $response->withStatus(302)->withHeader('Location', 'http://www.baidu.com');
        // $this->logger->addInfo("Something interesting happened", ['user' => 'admin', 'password' => 'admin']);
        return $response;
    }

    public function getFailOrders(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 15;

        Paginator::currentPathResolver(function () {
            return "/admin/getFailOrders";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $pays = Pay::where('status', '=', 1)->orderBy('id', 'DESC')->paginate($perPage);

        return $response->withJson($pays);
    }
}
