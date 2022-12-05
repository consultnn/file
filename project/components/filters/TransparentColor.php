<?php

namespace components\filters;

use Imagine\Filter\ImagineAware;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Vips\Image as VipsImage;

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
        assert($image instanceof ImagickImage);
        if ($image instanceof ImagickImage) {
            $image->getImagick()->opaquePaintImage(
                new \ImagickPixel($this->color),
                new \ImagickPixel('transparent'),
                10,
                false
            );
            return $image;
        }
        if ($image instanceof VipsImage) {
//            $image->getVips()
        }
    }
}