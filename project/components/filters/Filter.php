<?php

namespace components\filters;

use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Imagick\Imagine;

abstract class Filter
{
    public $width;
    public $height;
    public $image;
    public $ratio;
    public $box;

    public function __construct(ImageInterface $image, $width, $height)
    {
        $this->image = $image;
        $this->width = $width;
        $this->height = $height;
        $this->ratio =  $this->image->getSize()->getWidth() / $this->image->getSize()->getHeight();
        $this->setBox();
    }

    abstract public function getThumbnail();

    abstract public function setBox();

    public function create(BoxInterface $box, ColorInterface $color = null)
    {
        $imagine = new Imagine();
        $image = $imagine->create($box, $color);
        $image = $this->setMetadata($image);
        return $image;
    }

    /**
     * @param $image ImageInterface
     * @return ImageInterface
     */
    private function setMetadata($image)
    {
        foreach ($this->image->metadata()->toArray() as $key => $value) {
            $image->metadata()->offsetSet($key, $value);
        }
        return $image;
    }
}