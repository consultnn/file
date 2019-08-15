<?php

namespace components\filters\point;

class Top extends AbstractPoint
{
    public function getX()
    {
        return ceil(($this->thumbnailSize->getWidth() - $this->box->getWidth()) / 2);
    }
}
