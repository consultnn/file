<?php

namespace handlers;

use Psr\Http\Message\ResponseInterface;

/**
 * Class BaseHandler
 * @property \Application $app
 * @package handlers
 */
abstract class BaseHandler
{
    public $app;

    public function __construct(array $config)
    {
        foreach ($config as $name => $param) {
            if (property_exists($this, $name)) {
                $this->$name = $param;
            }
        }
    }

    abstract public function handle(): ResponseInterface;
}
