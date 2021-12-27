<?php

namespace components\params;

use components\Image;

interface ParamsSetter
{
    public const QUALITY_DEFAULT = 85;
    public const QUALITY_MAX = 90;

    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_PNG = 'png';

    public function __construct(array $params);
    public function apply(Image $image): void;
    public function noTransform(): bool;
}