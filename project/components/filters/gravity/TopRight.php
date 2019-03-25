<?php

namespace components\filters\gravity;

class TopRight extends AbstractGravity
{
    public function getX()
    {
        return $this->thumbnailSize->getWidth() - $this->box->getWidth();
    }
}