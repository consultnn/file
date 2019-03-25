<?php

namespace traits;

use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Font;
use Imagine\Imagick\Image;

trait WatermarkTrait
{
    public $project;
    private $text = '';
    private $fontSize = 3;
    private $hexColor = 'ffffff';
    private $font = 'roboto';
    private $opacity = 25;
    private $margin = 5;
    private $angle = -35;

    /**
     * @param $thumbnail Image
     * @return Image
     */
    public function generateWatermark($thumbnail)
    {
        if (!isset($this->watermark) && ($this->project == self::GIPER)) {
            $this->watermark = self::GIPER;
        }

        if (!empty($this->watermark)) {
            $size = getImageSize($this->path);
            $width	= (isset($this->width) ? $this->width : (isset($this->height) ? $this->height : 0));
            $height	= (isset($this->height) ? $this->height : (isset($this->width) ? $this->width : 0));

            if ($width >= $size[0] || empty($width)) {
                $width = $size[0];
            }

            if ($height >= $size[1] || empty($height)) {
                $height = $size[1];
            }

            $minSize = 150;
            $this->fontSize = sqrt($width * $height) / 45;
            switch ($this->watermark) {
                case self::GIPER:
                    $this->fontSize = sqrt($width * $height) / 80;
                    $this->text = 'GIPERNN.RU';
                    $this->font = 'generis';
                    break;
                case self::DOMOSTROY:
                    $this->text = 'DOMOSTROYNN.RU';
                    break;
                case self::DOMOSTROYDON:
                    $this->text = 'DomostroyDON.ru';
                    break;
                default:
                    $minSize = 100;
                    $this->text = $this->watermark;
                    $this->hexColor = '000000';
                    $this->opacity = 50;
            }
            $this->margin = $this->fontSize * 10;

            if (($width > $minSize) && ($height > $minSize)) {
                $thumbnail = $this->generateWm($thumbnail);
            }
        }
        return $thumbnail;
    }

    /**
     * @param $thumbnail Image
     * @return Image
     */
    private function generateWm($thumbnail)
    {
        $watermarkFontFile  = realpath(__DIR__.'/../web/fonts') . "/{$this->font}.ttf";
        $palette = new RGB();
        /** @var $thumbnail \Imagick */
        $watermarkFont = new Font($thumbnail->getImagick(), $watermarkFontFile, $this->fontSize, $palette->color($this->hexColor, $this->opacity));
        $text = $watermarkFont->box($this->text, $this->angle);

        $textWidth = $text->getWidth();
        $textHeight = $text->getHeight();

        $textOriginY = $textHeight + $this->margin;
        while (($textOriginY - $textHeight) < $thumbnail->getSize()->getHeight()) {
            $textOriginX = $this->margin;
            while ($textOriginX < $thumbnail->getSize()->getWidth()) {
                $thumbnail->draw()->text(
                    $this->text,
                    $watermarkFont,
                    new Point($textOriginX, $textOriginY),
                    $this->angle
                );
                $textOriginX += ($textWidth + $this->margin);
            }
            $textOriginY += ($textHeight + $this->margin);
        }
        return $thumbnail;
    }

}