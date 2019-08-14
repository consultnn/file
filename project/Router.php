<?php

use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use \FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;

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

    public function getDispatcher()
    {
        foreach ($this->_routes as $params => $handler) {
            list($method, $route) = explode(' ', $params);
            $this->_collector->addRoute(strtoupper($method), $route, $handler);
        }
        return new DispatcherGroupCountBased($this->_collector->getData());
    }
}