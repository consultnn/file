<?php

namespace traits;

use components\filters\point\AbstractPoint;
use components\filters\point\PointFactory;

trait PointTrait
{
    public function getPoint($size)
    {
        $pointFactory = new PointFactory($this->direction);
        $pointClassName = $pointFactory->getClassName();
        /** @var AbstractPoint $point */
        $point = new $pointClassName($size, $this->box);
        return $point->getPoint();
    }
}