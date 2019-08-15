<?php

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Zend\Diactoros\ServerRequest;

class Router
{
    private $_routes;
    private $_collector;

    public function __construct($routes)
    {
        $this->_routes = $routes;
        $routeParser = new Std;
        $dataGenerator = new GroupCountBased;
        $this->_collector = new RouteCollector($routeParser, $dataGenerator);
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
        $routInfo = $this->getDispatcher()->dispatch($request->getMethod(), $request->getUri()->getPath());
        $status = array_shift($routInfo);
        if ($status == Dispatcher::NOT_FOUND) {
            throw new Exception('', 404);
        }
        if ($status == Dispatcher::METHOD_NOT_ALLOWED) {
            throw new Exception('', 405);
        }
        list($handlerName, $params) = $routInfo;
        foreach ($params as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }
        return [$handlerName, $request];
    }
}