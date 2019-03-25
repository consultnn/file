<?php

namespace components\filters\gravity;

class Top extends AbstractGravity
{
    public function getX()
    {
        return ceil(($this->thumbnailSize->getWidth() - $this->box->getWidth()) / 2);
    }
}