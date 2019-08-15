<?php

namespace components\filters\point;

class TopRight extends AbstractPoint
{
    public function getX()
    {
        return $this->thumbnailSize->getWidth() - $this->box->getWidth();
    }
}
