<?php

namespace components;

use components\filters\Gravity;
use components\filters\gravity\AbstractGravity;
use components\filters\gravity\Bottom;
use components\filters\gravity\BottomLeft;
use components\filters\gravity\BottomRight;
use components\filters\gravity\Center;
use components\filters\gravity\Left;
use components\filters\gravity\Right;
use components\filters\gravity\Top;
use components\filters\gravity\TopLeft;
use components\filters\gravity\TopRight;
use components\filters\GravityFactory;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Filter\Transformation;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Imagine\Imagick\Imagine;
use traits\WatermarkTrait;

/**
 * Class Image
 * @property ImageInterface $image
 * @package components
 */
class Image
{
    use WatermarkTrait;

    const GIPER = 'gipernn';
    const DOMOSTROY = 'dom';
    const DOMOSTROYDON = 'domdon';

    public $params;
    public $image;
    public $width;
    public $height;
    public $savePath;
    public $quality;
    public $crop;
    public $path;
    public $format;
    public $watermark;

    public function __construct($params)
    {
        $this->params = $params;
        $this->setParams();
    }

    /**
     * @return \Imagine\Imagick\Image
     */
    public function generateImage()
    {
        $imagine = new Imagine();
        $transformation = new Transformation;
        $wm = false;
        if (empty($this->image)) {
            $this->image =  $imagine->open($this->path);
            $wm = true;
        }

        if (!empty($this->width) || !empty($this->height)) {
            $this->image = $this->getCropImage();
        }

        if (isset($this->params['ar'])) {
            $autorotate = new Autorotate;
            $autorotate->getTransformations($this->image);
            $this->image = $autorotate->apply($this->image);
        }
        if ($wm) {
            $this->image = $this->generateWatermark($this->image);
        }

        return $this->image;
    }

    private function getCropImage()
    {
        $imageSize = $this->image->getSize();
        $box = new Box($this->width, $this->height);
        if ($this->width === $imageSize->getWidth() && $this->height === $imageSize->getHeight()) {
            // The thumbnail size is the same as the wanted size.
            return $this->image;
        }
        $ratios = array(
            $this->width / $imageSize->getWidth(),
            $this->height / $imageSize->getHeight(),
        );

        // Crop the image so that it fits the wanted size
        $ratio = max($ratios);

        if ($imageSize->contains($box)) {
            // Downscale the image
            $imageSize = $imageSize->scale($ratio);
            $this->image->resize($imageSize);
            $thumbnailSize = $box;
        } else {
            $thumbnailSize = new Box(
                min($imageSize->getWidth(), $this->width),
                min($imageSize->getHeight(), $this->height)
            );
        }

        $gravityFactory = new GravityFactory($this->crop);
        $gravityClassName = $gravityFactory->getClassName();
        /** @var AbstractGravity $gravity */
        $gravity = new $gravityClassName($imageSize, $thumbnailSize);
        $this->image->crop($gravity->getPoint(), $thumbnailSize);

        return $this->image;
    }

    public function show()
    {
        $options = $this->getQualityOptions($this->format, $this->quality);
        $image = $this->generateImage();

        if ($this->savePath) {
            $image->save($this->savePath, $options);
        }
        return $image->show($this->format, $options);
    }

    /**
     * @param string $format
     * @param int $quality
     * @return array
     */
    private function getQualityOptions($format, $quality)
    {
        $options = [];

        switch ($format) {
            case 'png':
                $options['png_compression_filter'] = ceil($quality / 10);
                break;
            case 'jpg':
            case 'jpeg':
            case 'pjpeg':
                $options['jpeg_quality'] = $quality;
                break;
        }

        return $options;
    }

    private function setParams()
    {
        $widthParams = ['w', 'wl', 'wp', 'ws'];
        $widthKey = array_intersect($widthParams, array_keys($this->params));
        $this->width = !empty($widthKey) ? $this->params[reset($widthKey)] : null;

        $heightParams = ['h', 'hl', 'hp', 'hs'];
        $heightKey = array_intersect($heightParams, array_keys($this->params));
        $this->height = !empty($heightKey) ? $this->params[reset($heightKey)] : ($this->width ?? null);
        if ($this->width == null && !empty($this->height)) {
            $this->width = $this->height;
        }
        $this->format = isset($this->params['f']) ? $this->params['f'] : null;
        $this->quality = isset($this->params['q']) ? (int)$this->params['q'] : 85;
        $this->watermark = isset($this->params['wm']) ? $this->params['wm'] : null;
        $this->crop = isset($this->params['zc']) ? $this->params['zc'] : 1;
    }
}