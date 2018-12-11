<?php

namespace Weiming\Middlewares;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Exception\NotFoundException;
use \Weiming\Libs\Utils;
use \Weiming\Middlewares\BaseMiddleware;

class WhelloteListMiddleware extends BaseMiddleware
{
    /**
     * 白名单中间件
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
        $pattern      = $route->getPattern();
        $ip           = Utils::getIp();
        $whelloteList = $this->settings['whelloteList'];
        if (strpos($pattern, '/admin') !== false && !in_array($ip, $whelloteList)) {
            return $response->withJson([
                'status' => 1,
                'msg'    => 'You are denied access, please contact the administrator.',
                'data'   => [],
            ]);
        }
        return $next($request, $response);
    }
}
