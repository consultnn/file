<?php

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Zend\Diactoros\ServerRequestFactory;

class Application
{
    public $request;

    private $_project;
    private static $_config;

    public function __construct($config)
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->setConfig($config);
    }

    public function run()
    {
        $dispatcher = simpleDispatcher(function(RouteCollector $collector) {
            foreach (self::getConfigValue('routes') as $params => $handler) {
                list($method, $route) = explode(' ', $params);
                if (empty($route)) {
                    continue;
                }
                $collector->addRoute(strtoupper($method), $route, $handler);
            }
        });

        $httpMethod = $this->request->getServerParams()['REQUEST_METHOD'];
        $uri = $this->request->getServerParams()['REQUEST_URI'];

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND :
                $handler = $routeInfo[1];
                $values = $routeInfo[2];
                foreach ($values as $name => $value) {
                    $this->request = $this->request->withAttribute($name, $value);
                }
                $this->request = $this->setProjectToRequest();
                return call_user_func([new $handler, 'handle'], $this->request);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED :
                return (new Response())->withStatus(405);
            default :
                return (new Response())->withStatus(404);
        }
    }

    private function setProjectToRequest()
    {
        return $this->request->withAttribute('project', $this->_project);
    }

    public function setConfig($config)
    {
        $this->_project = $this->request->getServerParams()['DOMAIN'];
        self::$_config =  array_merge($config['default'], $config[$this->_project]);
    }

    public static function getConfigValue($name)
    {
        if (empty(self::$_config) || !array_key_exists($name, self::$_config)) {
            return null;
        }

        return self::$_config[$name];
    }
}