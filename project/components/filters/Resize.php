<?php

namespace components\filters;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class Resize extends Filter
{
    public $aoe;
    public $background;

    public function __construct(ImageInterface $image, $width, $height, $aoe = false, $background)
    {
        parent::__construct($image, $width, $height);
        $this->aoe = $aoe;
        $this->background = $background;
    }

    public function setBox()
    {
        if (empty($this->width) && empty($this->height)) {
            $this->width = $this->image->getSize()->getWidth();
            $this->height = $this->image->getSize()->getHeight();
        } else if (empty($this->width) || empty($this->height)) {
            if (empty($this->width)) {
                $this->width = $this->height * $this->ratio;
            } else {
                $this->height = $this->width / $this->ratio;
            }
        }

        return $this->box = new Box($this->width, $this->height);
    }

    public function getThumbnail() : ImageInterface
    {
        $thumbnailParam = $this->aoe ? ImageInterface::THUMBNAIL_FLAG_UPSCALE : ImageInterface::THUMBNAIL_INSET;
        $thumbnail = $this->image->thumbnail($this->box, $thumbnailParam);
        if ($this->background) {
            $thumbnailWithBackground = $this->create($thumbnail->getSize(), $this->background);
            $thumbnail = $thumbnailWithBackground->paste($thumbnail, new Point(0, 0));
        }
        return $thumbnail;
    }
}