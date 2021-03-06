<?php

namespace Simplon\Core\Middleware;

class MiddlewareCollection
{
    /**
     * @var array
     */
    private $middleware = [];

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->middleware;
    }

    /**
     * @param mixed $middleware
     *
     * @return MiddlewareCollection
     */
    public function add($middleware): MiddlewareCollection
    {
        $this->middleware[] = $middleware;

        return $this;
    }
}