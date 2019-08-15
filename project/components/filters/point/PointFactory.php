<?php

namespace components\filters\point;

class PointFactory
{
    private $_type;

    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        switch ($this->_type) {
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
            case '1':
            default :
                $gravityClassName = Center::class;
        }
        return $gravityClassName;
    }
}
