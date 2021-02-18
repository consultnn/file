<?php

namespace components\filters;

use Imagine\Filter\ImagineAware;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Image;

class TransparentColor extends ImagineAware
{
    public $color;
    /**
     * Applies scheduled transformation to an ImageInterface instance.
     * @param \Imagine\Image\ImageInterface $image
     * @return \Imagine\Image\ImageInterface returns the processed ImageInterface instance
     */
    public function apply (ImageInterface $image): ImageInterface
    {
        assert($image instanceof Image);
        $image->getImagick()->opaquePaintImage(
            new \ImagickPixel($this->color),
            new \ImagickPixel('transparent'),
            10,
            false
        );
        return $image;
    }
}