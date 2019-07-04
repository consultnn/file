<?php

namespace components\filters\point;

class BottomRight extends AbstractPoint
{
    public function getX()
    {
        return $this->thumbnailSize->getWidth() - $this->box->getWidth();
    }

    public function getY()
    {
        return $this->thumbnailSize->getHeight() - $this->box->getHeight();
    }
}