<?php

namespace components\filters\point;

class Center extends AbstractPoint
{
    public function getX()
    {
        return ceil(($this->thumbnailSize->getWidth() - $this->box->getWidth()) / 2);
    }

    public function getY()
    {
        return ceil(($this->thumbnailSize->getHeight() - $this->box->getHeight()) / 2);
    }
}
