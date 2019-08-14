<?php

namespace handlers;

use Application;
use Psr\Http\Message\ResponseInterface;

abstract class BaseHandler
{
    public $app;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    abstract public function handle(): ResponseInterface;
}