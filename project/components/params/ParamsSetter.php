<?php

namespace components\params;

use components\Image;

interface ParamsSetter
{
    public const QUALITY_DEFAULT = 85;
    public const QUALITY_MAX = 90;

    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_PNG = 'png';

    public function __construct(Image $image, array $params);
    public function apply(): void;
}