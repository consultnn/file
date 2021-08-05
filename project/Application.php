<?php

use exceptions\HttpException;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequestFactory;

/**
 * Class Application
 * @property \components\Filesystem $filesystem
 * @property \Laminas\Diactoros\ServerRequest $request
 * @property string $project
 * @property array $components
 */
class Application
{
    public $request;
    public $project = null;
    public $components;
    private $_filesystem;

    public function __construct()
    {
        $this->request = ServerRequestFactory::fromGlobals();
    }

    public function run($config): \Psr\Http\Message\ResponseInterface
    {
        $this->project = $this->request->getServerParams()['DOMAIN'];
        if (!array_key_exists($this->project, $config['projects'])) {
            return new EmptyResponse(400);
        }
        foreach ($config['app']['components'] as $name => $component) {
            $this->components[$name] = $component;
        }
        $projectRoutes = $config['projects'][$this->project]['routes'] ?? [];
        $routerConfig = array_merge($config['app']['routes'], $projectRoutes);
        $router = new Router($routerConfig);

        try {
            list($handlerName, $this->request) = $router->dispatch($this->request);
            $projectHandlerConfig = $config['projects'][$this->project]['handler'][$handlerName] ?? [];
            $projectConfig = $config['app']['handler'][$handlerName] ?? [];
            $handlerConfig = array_merge($projectConfig, $projectHandlerConfig);
            $handlerConfig['app'] = $this;
            $class = new $handlerConfig['class']($handlerConfig);
            /** @var $class \handlers\BaseHandler */
            return $class->handle();
        } catch (HttpException $e) {
            return new EmptyResponse($e->getCode(), ['X-Reason' => $e->getMessage()]);
        } catch (Exception $e) {
            return new EmptyResponse(500, ['X-Reason' => $e->getMessage()]);
        }
    }

    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        throw new Exception("Attribute {$name} does not exists");
    }

    public function getFilesystem()
    {
        if ($this->_filesystem) {
            return $this->_filesystem;
        }
        $config = $this->components['filesystem'];
        return $this->_filesystem = new $config['class'](['project' => $this->project]);
    }

    public function echoResponse(\Psr\Http\Message\ResponseInterface $response): void
    {
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        echo $response->getBody()->getContents();
    }
}
