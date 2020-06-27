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

    public function apply(ImageInterface $image): void
    {
        $size = $image->getSize();
        $imageWidth = $size->getWidth();
        $imageHeight = $size->getHeight();
        $needWatermark = ($imageWidth > $this->minSize) && ($imageHeight > $this->minSize);
        if (!$needWatermark) {
            return;
        }

        $fontSize = round(sqrt($imageWidth * $imageHeight) / $this->fontSizeCoefficient);

        $watermark = $this->watermark($fontSize);
        $textBox = $watermark->getSize();
        $margin = round($fontSize * $this->marginCoefficient);
        $textWidth = $textBox->getWidth();
        $textHeight = $textBox->getHeight();

        $textOriginY = $textHeight;
        $count = 0;
        while ($textOriginY + $textHeight < $imageHeight) {
            $textOriginX = $margin / 2;
            while ($textOriginX < $imageWidth) {
                $count++;
                $image->paste($watermark, new Point($textOriginX, $textOriginY));
                $textOriginX += $textWidth + $margin;
            }
            $textOriginY += $margin;
        }
    }

    private function watermark(int $fontSize): ImageInterface
    {
        $hash = md5(serialize([
            $this->angle,
            $fontSize,
            $this->font,
            $this->hexColor,
            $this->text,
            $this->opacity,
            $this->marginCoefficient,
        ]));

        $cacheFile = RUNTIME_DIR . 'watermark-' . $hash . '.png';

        if (!file_exists($cacheFile)) {
            $palette = new RGB();
            $fontFile  = dirname(__DIR__) . "/web/fonts/{$this->font}.ttf";
            $imagine = new \Imagine\Imagick\Imagine();
            $watermarkFont = $imagine->font($fontFile, $fontSize, $palette->color($this->hexColor, $this->opacity));
            $watermark = $imagine->create($watermarkFont->box($this->text), $palette->color(0xFFFFFF, 0));
            $watermark->draw()->text($this->text, $watermarkFont, new Point(0, 0));
            $watermark->rotate($this->angle, $palette->color(0xFFFFFF, 0));
            $watermark->save($cacheFile);
        }

        return $this->imagine->open($cacheFile);
    }
}
