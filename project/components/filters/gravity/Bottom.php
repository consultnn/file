<?php

namespace components\filters\gravity;

class Bottom extends AbstractGravity
{
    public function getX()
    {
        return ceil(($this->thumbnailSize->getWidth() - $this->box->getWidth()) / 2);
    }

    public function getY()
    {
        return $this->thumbnailSize->getHeight() - $this->box->getHeight();
    }
}