<?php

namespace components;

use components\filters\Crop;
use components\filters\ForceAspectRatio;
use components\filters\Resize;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Imagick\Imagine;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

//use Imagine\Vips\Imagine;

/**
 * Class Image
 * @package components
 * TODO режим stc (указанный цвет делаем прозрачным)
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

    /** @var \Imagine\Image\ImageInterface */
    public $sourceImage;
    /** @var array */
    public $options;
    /** @var int  */
    public $quality = self::QUALITY_DEFAULT;
    /** @var string */
    public $format;
    /** @var string */
    public $crop;
    /** @var array */
    public $params;
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
    /** @var string */
    public $watermark;
    /** @var bool */
    public $autoRotate;
    /** @var string */
    public $savePath;
    /** @var array */
    public $watermarkConfig;
    /** @var string */
    public $setTransparentColor;

    public function __construct($path, $params, $extension)
    {
        $this->sourceImage = (new Imagine)->open($path);
        $this->params = $params;
        $this->format = $extension;
        $this->setParams();
    }

    public function show(): StreamInterface
    {
        assert($this->savePath !== null);
        $image = $this->generateImage();
        $image->save($this->savePath, $this->options);
        return new Stream($this->savePath);
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

        if (isset($this->params['stc'])) {
            /** TODO поиск пикселей похожего цвета и замена их на прозрачный. На выходе требуем png, gif или webp */
            $this->setTransparentColor = $this->params['stc'];
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
            /** TODO подозрительная смена типа */
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

        /**  TODO аналогично для WebP */
        if ($this->format === self::FORMAT_PNG) {
            $this->setOption('png_compression_level', 8);
        }
    }

    private function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }
}
