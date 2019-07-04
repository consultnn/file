<?php

namespace components\filters;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use traits\PointTrait;

class Crop extends Filter
{
    use PointTrait;

    public $direction;

    public function __construct($image, $width, $height, $direction)
    {
        parent::__construct($image, $width, $height);
        $this->direction = $direction;
    }

    public function setBox()
    {
        if (empty($this->width) && empty($this->height)) {
            $this->width = $this->height = min($this->image->getSize()->getWidth(), $this->image->getSize()->getHeight());
        } else if (empty($this->width) || empty($this->height)) {
            if (empty($this->width)) {
                $this->width = $this->height;
            } else {
                $this->height = $this->width;
            }
        }

        return $this->box = new Box($this->width, $this->height);
    }

    public function getThumbnail() : ImageInterface
    {
        $thumbnail = $this->image->thumbnail($this->getThumbnailSize(), ImageInterface::THUMBNAIL_FLAG_UPSCALE);
        $thumbnail->crop($this->getPoint($thumbnail->getSize()), $this->box);
        return $thumbnail;
    }

    public function getThumbnailSize()
    {
        $ratio = $this->width / $this->height;
        if ($this->ratio > 1) {
            if ($ratio > 1) {
                $width = $this->width;
                $height = $this->width / $this->ratio;
            } else {
                $width = $this->height * $this->ratio;
                $height = $this->height;
            }
        } else {
            if ($ratio > 1) {
                $width = $this->height * $this->ratio;
                $height = $this->height;
            } else {
                $width = $this->width;
                $height = $this->width / $this->ratio;
            }
        }

        return new Box($width, $height);
    }
}