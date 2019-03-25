<?php

namespace components\filters\gravity;

class BottomRight extends AbstractGravity
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