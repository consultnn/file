<?php

use exceptions\HttpException;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Laminas\Diactoros\ServerRequest;

class Router
{
    private $_routes;
    private $_collector;

    public function __construct($routes)
    {
        $this->_routes = $routes;
        $this->_collector = new RouteCollector(new Std, new GroupCountBased);
    }

    private function getDispatcher()
    {
        foreach ($this->_routes as $params => $handler) {
            list($method, $route) = explode(' ', $params);
            $this->_collector->addRoute(strtoupper($method), $route, $handler);
        }
        return new DispatcherGroupCountBased($this->_collector->getData());
    }

    public function dispatch(ServerRequest $request)
    {
        list($status, $handlerName, $params) = array_pad($this->getDispatcher()->dispatch($request->getMethod(), $request->getUri()->getPath()), 3, null);
        if ($status == Dispatcher::NOT_FOUND) {
            throw new HttpException('', 404);
        }
        if ($status == Dispatcher::METHOD_NOT_ALLOWED) {
            throw new HttpException('', 405);
        }
        foreach ($params as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }
        return [$handlerName, $request];
    }
}
