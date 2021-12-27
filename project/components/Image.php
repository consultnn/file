<?php

namespace components;

use components\filters\Crop;
use components\filters\ForceAspectRatio;
use components\filters\Resize;
use components\filters\TransparentColor;
use components\params\LegacyParamsSetter;
use components\params\ParamsSetter;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

//use Imagine\Vips\Imagine;

/**
 * Class Image
 * @package components
 */
class Image
{
    /** @var \Imagine\Image\ImageInterface */
    public $sourceImage;
    /** @var array */
    public $options = [];
    /** @var int  */
    public $quality = ParamsSetter::QUALITY_DEFAULT;
    /** @var string */
    public $format;
    /** @var string */
    public $crop;
    /** @var integer */
    public $width;
    /** @var integer */
    public $height;
    /** @var string */
    public $far;
    /** @var string */
    public $background;
    /** @var bool */
    public $aoe;
    /** @var bool */
    public $watermark;
    /** @var bool */
    public $autoRotate;
    /** @var string */
    public $savePath;
    /** @var array */
    public $watermarkConfig = [];
    /** @var string */
    public $setTransparentColor;

    public function __construct(string $path, string $extension)
    {
        $this->sourceImage = (new Imagine)->open($path);
        $this->format = $extension;
    }

    public function show(): StreamInterface
    {
        assert($this->savePath !== null);
        $image = $this->generateImage();
        $image->save($this->savePath, $this->options);
        return new Stream($this->savePath);
    }

    public function setOption(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    public function generateImage(): ImageInterface
    {
        if ($this->crop) {
            $paramClass = new Crop($this->sourceImage, $this->width, $this->height, $this->crop);
        } elseif ($this->far) {
            $paramClass = new ForceAspectRatio($this->sourceImage, $this->width, $this->height, $this->far, $this->background);
        } else {
            $paramClass = new Resize($this->sourceImage, $this->width, $this->height, $this->background, $this->aoe);
        }
        $image = $paramClass->getThumbnail();

        if ($this->autoRotate) {
            $autorotate = new Autorotate;
            $autorotate->getTransformations($image);
            $image = $autorotate->apply($image);
        }

        if ($this->setTransparentColor) {
            $transparent = new TransparentColor();
            $transparent->color = $this->setTransparentColor;
            $transparent->apply($image);
        }

        if ($this->watermark) {
            $watermark = new Watermark(new Imagine(), $this->watermarkConfig);
            $watermark->apply($image);
        }

        return $image;
    }
}
