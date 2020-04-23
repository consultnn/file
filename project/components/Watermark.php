<?php

namespace components;

use Imagine\Image\AbstractImagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

class Watermark
{
    public $text;
    public $fontSizeCoefficient;
    public $hexColor;
    public $font;
    public $opacity;
    public $marginCoefficient;
    public $angle;
    public $minSize;
    protected $imagine;

    public function __construct(AbstractImagine $imagine, array $config)
    {
        foreach ($config as $name => $param) {
            if (property_exists($this, $name)) {
                $this->$name = $param;
            }
        }
        $this->imagine = $imagine;
    }

    public function apply(ImageInterface $image) {
        $size = $image->getSize();
        $imageWidth = $size->getWidth();
        $imageHeight = $size->getHeight();
        $needWatermark = ($imageWidth > $this->minSize) && ($imageHeight > $this->minSize);
        if (!$needWatermark) {
            return;
        }

        $watermarkFontFile  = dirname(__DIR__) . "/web/fonts/{$this->font}.ttf";
        $palette = new RGB();
        $fontSize = sqrt($imageWidth * $imageHeight) / $this->fontSizeCoefficient;

        $watermarkFont = $this->imagine->font($watermarkFontFile, $fontSize, $palette->color($this->hexColor, $this->opacity));
        $text = $watermarkFont->box($this->text, $this->angle);

        $textWidth = $text->getWidth();
        $textHeight = $text->getHeight();
        $margin = $fontSize * $this->marginCoefficient;
        $textOriginY = $textHeight + $margin;
        while (($textOriginY - $textHeight) < $imageHeight) {
            $textOriginX = $margin;
            while ($textOriginX < $imageWidth) {
                $image->draw()->text(
                    $this->text,
                    $watermarkFont,
                    new Point($textOriginX, $textOriginY),
                    $this->angle
                );
                $textOriginX += ($textWidth + $margin);
            }
            $textOriginY += ($textHeight + $margin);
        }
    }
}
