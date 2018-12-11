<?php

namespace Weiming\Middlewares;

class BaseMiddleware
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($obj)
    {
        return $this->container->{$obj};
    }
}
