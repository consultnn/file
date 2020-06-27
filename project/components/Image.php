<?php

namespace components;

use components\filters\Crop;
use components\filters\ForceAspectRatio;
use components\filters\Resize;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Imagick\Imagine;
//use Imagine\Vips\Imagine;

/**
 * Class Image
 * @property \Imagine\Imagick\Image $sourceImage
 * @property array $options
 * @property int $quality
 * @property string $format
 * @property string $crop
 * @property array $params
 * @property integer $width
 * @property integer $height
 * @property string $far
 * @property string $background
 * @property bool $aoe
 * @property string $watermark
 * @property bool $autoRotate
 * @property string $savePath
 * @property array $watermarkConfig
 * @package components
 */
class Image
{
    const GIPER = 'gipernn';
    const DOMOSTROY = 'dom';
    const DOMOSTROYDON = 'domdon';
    const FORMAT_JPEG = 'jpeg';
    const FORMAT_PNG = 'png';
    const QUALITY_DEFAULT = 85;
    const QUALITY_MAX = 90;

    public $sourceImage;
    public $options;
    public $quality = self::QUALITY_DEFAULT;
    public $format;
    public $crop;
    public $params;
    public $width;
    public $height;
    public $far;
    public $background;
    public $aoe;
    public $watermark;
    public $autoRotate;
    public $savePath;
    public $watermarkConfig;

    public function __construct($path, $params, $extension)
    {
        $this->sourceImage = (new Imagine)->open($path);
        $this->params = $params;
        $this->format = $extension;
        $this->setParams();
    }

    public function show(): ManipulatorInterface
    {
        $image = $this->generateImage();

        if ($this->savePath) {
            return $image->save($this->savePath, $this->options);
        }

        return $image->show($this->format, $this->options);
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

        if ($this->watermark) {
            (new Watermark(new Imagine(), $this->watermarkConfig))->apply($image);
        }

        return $image;
    }

    private function oneOfThese($values)
    {
        foreach ($values as $key => $value) {
            if (isset($this->params[$value])) {
                return $this->params[$value];
            }
        }
        return null;
    }

    private function setParams()
    {
        if (isset($this->params['q'])) {
            $this->quality = min($this->params['q'], self::QUALITY_MAX);
        }
        $this->setOption('quality', $this->quality);

        if (isset($this->params['zc'])) {
            $this->crop = $this->params['zc'];
        }
        $ratio = $this->sourceImage->getSize()->getWidth() / $this->sourceImage->getSize()->getHeight();
        if ($ratio > 1) {
            $widthParams = ['wl', 'w'];
            $heightParams = ['hl', 'h'];
        } elseif ($ratio < 1) {
            $widthParams = ['wp', 'w'];
            $heightParams = ['hp', 'h'];
        } else {
            $widthParams = ['ws', 'wl', 'wp', 'w'];
            $heightParams = ['hs', 'hl', 'hp', 'h'];
        }
        $this->width = $this->oneOfThese($widthParams);
        $this->height = $this->oneOfThese($heightParams);

        if (isset($this->params['far']) && empty($this->crop)) {
            $this->far = $this->params['far'];
        }

        if (isset($this->params['bg'])) {
            $this->background = (new RGB())->color('#' . $this->params['bg']);
            $this->format = self::FORMAT_JPEG;
        }

        if (isset($this->params['aoe'])) {
            $this->aoe = true;
        }

        if (isset($this->params['wm']) && $this->params['wm'] != 0) {
            $this->watermark = $this->params['wm'];
        }

        if (isset($this->params['ar'])) {
            $this->autoRotate = true;
        }

        $this->setFormat();
    }

    private function setFormat()
    {
        if (isset($this->params['f'])) {
            $this->format = $this->params['f'];
        }
        $this->setOption('format', $this->format);

        if ($this->format === self::FORMAT_PNG) {
            $this->setOption('png_compression_level', 8);
        }
    }

    private function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }
}
