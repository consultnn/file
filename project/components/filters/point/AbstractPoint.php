<?php

namespace components\filters\point;

use Imagine\Image\BoxInterface;
use Imagine\Image\Point;

/**
 * Class AbstractPoint
 * @property BoxInterface $box
 * @property BoxInterface $thumbnailSize
 * @package components\filters\point
 */
abstract class AbstractPoint
{
    public $box;
    public $thumbnailSize;

    public function __construct(BoxInterface $box, BoxInterface $thumbnailSize)
    {
        $this->box = $box;
        $this->thumbnailSize = $thumbnailSize;
    }

    public function getX()
    {
        return 0;
    }

    public function getY()
    {
        return 0;
    }

    public function getPoint()
    {
        return new Point(abs($this->getX()), abs($this->getY()));
    }
}
