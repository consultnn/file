<?php

namespace components\filters\point;

class Bottom extends AbstractPoint
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
