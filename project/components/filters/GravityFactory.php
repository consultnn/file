<?php

namespace components\filters;

use components\filters\gravity\Bottom;
use components\filters\gravity\BottomLeft;
use components\filters\gravity\BottomRight;
use components\filters\gravity\Center;
use components\filters\gravity\Left;
use components\filters\gravity\Right;
use components\filters\gravity\Top;
use components\filters\gravity\TopLeft;
use components\filters\gravity\TopRight;

class GravityFactory
{
    private $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        switch ($this->type) {
            case 'TL':
                $gravityClassName = TopLeft::class;
                break;
            case 'T':
                $gravityClassName = Top::class;
                break;
            case 'TR':
                $gravityClassName = TopRight::class;
                break;
            case 'L':
                $gravityClassName = Left::class;
                break;
            case 'R':
                $gravityClassName = Right::class;
                break;
            case 'BL':
                $gravityClassName = BottomLeft::class;
                break;
            case 'B':
                $gravityClassName = Bottom::class;
                break;
            case 'BR':
                $gravityClassName = BottomRight::class;
                break;
            case 'C':
            case 1:
            default :
                $gravityClassName = Center::class;
        }
        return $gravityClassName;
    }
}