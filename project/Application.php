<?php

use Zend\Diactoros\ServerRequestFactory;

/**
 * Class Application
 * @property \League\Flysystem\Filesystem $filesystem
 */
class Application
{
    public $request;
    public $response;
    public $project = null;
    public $components;

    public function __construct()
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->response = new Response;
    }

    public function run($config)
    {
        $this->project = $this->request->getServerParams()['DOMAIN'];
        if (!array_key_exists($this->project, $config['projects'])) {
            return $this->response->withStatus(404);
        }
        foreach ($config['app']['components'] as $name => $component) {
            $this->components[$name] = $component;
        }
        $projectRoutes = isset($config['projects'][$this->project]['routes']) ? $config['projects'][$this->project]['routes'] : [];
        $routConfig = array_merge($config['app']['routes'], $projectRoutes);

        $router = new Router($routConfig);

        try {
            list($handlerName, $this->request) = $router->dispatch($this->request);
            $projectHandlerConfig = isset($config['projects'][$this->project]['handler'][$handlerName]) ? $config['projects'][$this->project]['handler'][$handlerName] : [];
            $handlerConfig = array_merge($config['app']['handler'][$handlerName], $projectHandlerConfig);
            $handlerConfig['app'] = $this;
            $class = new $handlerConfig['class']($handlerConfig);
            /** @var $class \handlers\BaseHandler */
            return $class->handle();
        } catch (Exception $e) {
            return $this->response->withStatus($e->getCode());
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if (array_key_exists($name, $this->components)) {
            $component = $this->components[$name];
            return new $component['class'];
        }
    }
}
