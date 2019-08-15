<?php

namespace handlers;

use Application;
use Psr\Http\Message\ResponseInterface;

abstract class BaseHandler
{
    public $app;

    public function __construct(Application $application, $config)
    {
        foreach ($config as $name => $param) {
            if (property_exists($this, $name)) {
                $this->$name = $param;
            }
        }
        $this->app = $application;
    }

    abstract public function handle(): ResponseInterface;
}