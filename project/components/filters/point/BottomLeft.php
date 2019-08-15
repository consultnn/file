<?php

namespace components\filters\point;

class BottomLeft extends AbstractPoint
{
    public function getY()
    {
        return $this->thumbnailSize->getHeight() - $this->box->getHeight();
    }
}
