<?php

namespace components\filters\gravity;

class Left extends AbstractGravity
{
    public function getY()
    {
        return ceil(($this->thumbnailSize->getHeight() - $this->box->getHeight()) / 2);
    }
}