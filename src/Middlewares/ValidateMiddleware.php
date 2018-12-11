<?php

namespace Weiming\Middlewares;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Middlewares\BaseMiddleware;

class ValidateMiddleware extends BaseMiddleware
{
    /**
     * 提交数据验证中间件
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
        $isPost   = $request->isPost();
        $isPut    = $request->isPut();
        $isGet    = $request->isGet();
        $isDelete = $request->isDelete();
        // 数据
        $datas = file_get_contents('php://input');
        // 路由
        $path = $request->getUri()->getPath();
        // 验证
        if ($isPost && (strpos($path, 'addField') !== false || strpos($path, 'editField') !== false)) {
        }

        if ($messages) {
            return $response->withJson(['status' => 1, 'msg' => implode('', $messages), 'data' => []]);
        }

        $response = $next($request, $response);
        return $response;
    }
}
