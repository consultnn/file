<?php

namespace components\filters\point;

class Left extends AbstractPoint
{
    public function getY()
    {
        return ceil(($this->thumbnailSize->getHeight() - $this->box->getHeight()) / 2);
    }
}
