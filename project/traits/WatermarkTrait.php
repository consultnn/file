<?php

namespace traits;

use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Font;
use Imagine\Imagick\Image;

trait WatermarkTrait
{
    public $text;
    public $fontSizeCoefficient;
    public $hexColor;
    public $font;
    public $opacity;
    public $marginCoefficient;
    public $angle;
    public $minSize;

    public function generateWatermark($image)
    {
        if (!empty($this->watermarkConfig)) {
            $this->setConfigParams();
            if (!empty($this->watermark) && ($this->width > $this->minSize) && ($this->height > $this->minSize)) {
                $this->generateWm($image);
            }
        }
    }

    /**
     * @param $image Image
     */
    private function generateWm($image)
    {
        $watermarkFontFile  = realpath(__DIR__ . '/../web/fonts') . "/{$this->font}.ttf";
        $palette = new RGB();
        $fontSize = sqrt($this->width * $this->height) / $this->fontSizeCoefficient;
        $watermarkFont = new Font($image->getImagick(), $watermarkFontFile, $fontSize, $palette->color($this->hexColor, $this->opacity));
        $text = $watermarkFont->box($this->text, $this->angle);

        $textWidth = $text->getWidth();
        $textHeight = $text->getHeight();
        $margin = $fontSize * $this->marginCoefficient;
        $textOriginY = $textHeight + $margin;
        while (($textOriginY - $textHeight) < $image->getSize()->getHeight()) {
            $textOriginX = $margin;
            while ($textOriginX < $image->getSize()->getWidth()) {
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

    private function setConfigParams()
    {
        foreach ($this->wmConfig as $name => $param) {
            if (property_exists($this, $name)) {
                $this->$name = $param;
            }
        }
    }
}
