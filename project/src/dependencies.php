<?php

use Bnf\Slim3Psr15\CallableResolver;

$container = $app->getContainer();

$container['callableResolver'] = function ($container) {
    return new CallableResolver($container);
};
