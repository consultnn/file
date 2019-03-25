<?php

namespace components\filters\gravity;

class Right extends AbstractGravity
{
    public function getX()
    {
        return $this->thumbnailSize->getWidth() - $this->box->getWidth();
    }

    public function getY()
    {
        return ceil(($this->thumbnailSize->getHeight() - $this->box->getHeight()) / 2);
    }
}