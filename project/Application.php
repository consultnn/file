<?php

use FastRoute\Dispatcher;
use Zend\Diactoros\ServerRequestFactory;

class Application
{
    public $request;
    public $response;
    public $config;
    public $project = null;

    public function __construct($config)
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->response = new Response;
        $this->setConfig($config);
    }

    public function run()
    {
        if (empty($this->project)) {
            return $this->response->withStatus(404);
        }
        $router = new Router($this->config['routes']);
        $dispatcher = $router->getDispatcher();

        list($status, $handlerName, $values) = $dispatcher->dispatch($this->request->getMethod(), $this->request->getUri()->getPath());

        switch ($status) {
            case Dispatcher::FOUND :
                foreach ($values as $name => $value) {
                    $this->request = $this->request->withAttribute($name, $value);
                }
                $handler =  '\handlers\\' . ucfirst($handlerName);
                $handlerConfig = $this->config['handler'];
                unset($this->config['handler']);
                $this->config += $handlerConfig[$handlerName];
                $class = new $handler($this);
                /** @var $class \handlers\BaseHandler */
                return $class->handle();
            case Dispatcher::METHOD_NOT_ALLOWED :
                return $this->response->withStatus(405);
            default :
                return $this->response->withStatus(404);
        }
    }

    public function setConfig($config)
    {
        $domain = $this->request->getServerParams()['DOMAIN'];

        if (array_key_exists($domain, $config['projects'])) {
            $this->project = $domain;
            $projectConfigs = $config['projects'];
            unset($config['projects']);
            $this->config =  array_merge($config, $projectConfigs[$this->project]);
        }
    }
}