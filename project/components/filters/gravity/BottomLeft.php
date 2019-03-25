<?php

namespace components\filters\gravity;

class BottomLeft extends AbstractGravity
{
    public function getY()
    {
        return $this->thumbnailSize->getHeight() - $this->box->getHeight();
    }
}