<?php

namespace components;

use Imagine\Filter\Basic\Autorotate;
use Imagine\Filter\Transformation;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Imagick\Imagine;
use traits\WatermarkTrait;

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
    public $thumbnailMode;
    public $saveName;
    public $savePath;
    public $quality;
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
        $this->image = $imagine->open($this->path);

        if (!empty($this->width) || !empty($this->height)) {
            $box = new Box(($this->width ?? $this->height), ($this->height ?? $this->width));

            $transformation->thumbnail($box, $this->thumbnailMode);
            $this->image = $transformation->apply($this->image);
        }

        if (isset($this->params['ar'])) {
            $autorotate = new Autorotate;
            $autorotate->getTransformations($this->image);
            $this->image = $autorotate->apply($this->image);
        }

        $options = $this->getQualityOptions($this->format, $this->quality);
        $this->image = $this->generateWatermark($this->image);
        $this->image->save($this->savePath)->show($this->format, $options);
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
        $this->height = !empty($heightKey) ? $this->params[reset($heightKey)] : null;

        $this->thumbnailMode = isset($this->params['zc']) ? ImageInterface::THUMBNAIL_OUTBOUND : ImageInterface::THUMBNAIL_INSET;
        $this->format = isset($this->params['f']) ? $this->params['f'] : null;
        $this->quality = isset($this->params['q']) ? (int)$this->params['q'] : 85;
        $this->watermark = isset($this->params['wm']) ? $this->params['wm'] : null;
    }
}