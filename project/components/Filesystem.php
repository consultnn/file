<?php

namespace components;

use League\Flysystem\Adapter\Local;

class Filesystem extends \League\Flysystem\Filesystem
{
    public function __construct()
    {
        $adapter = new Local(STORAGE_DIR);
        parent::__construct($adapter);
    }
}
