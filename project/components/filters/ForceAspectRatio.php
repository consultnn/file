<?php

namespace components\filters;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use traits\PointTrait;

class ForceAspectRatio extends Filter
{
    use PointTrait;

    public $direction;
    public $background;

    public function __construct(ImageInterface $image, $width, $height, $direction, $background = null)
    {
        parent::__construct($image, $width, $height);
        $this->direction = $direction;
        $this->background = $background;
    }

    public function getThumbnail() : ImageInterface
    {
        $thumbnail = $this->image->thumbnail($this->box, ImageInterface::THUMBNAIL_FLAG_UPSCALE);
        if ($this->background) {
            $thumbnailWithBackground = $this->create($this->box, $this->background);
            $thumbnail = $thumbnailWithBackground->paste($thumbnail, $this->getPoint($thumbnail->getSize()));
        }

        return $thumbnail;
    }

    public function setBox()
    {
        if (empty($this->width) && empty($this->height)) {
            $this->width = $this->image->getSize()->getWidth();
            $this->height = $this->image->getSize()->getHeight();
        } else if (empty($this->width) || empty($this->height)) {
            $imageSize = $this->image->getSize();
            $ratio = $imageSize->getWidth() / $imageSize->getHeight();
            if (empty($this->width)) {
                $this->width = $this->height * $ratio;
            } else {
                $this->height = $this->width / $ratio;
            }
        }

        return $this->box = new Box($this->width, $this->height);
    }

}