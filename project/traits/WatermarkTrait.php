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

    public function generateWatermark($image)
    {
        if (!isset($this->watermark) && ($this->project == self::GIPER)) {
            $this->watermark = self::GIPER;
        }

        if (!empty($this->watermark)) {
            $minSize = 150;
            $this->fontSize = sqrt($this->width * $this->height) / 45;
            switch ($this->watermark) {
                case self::GIPER:
                    $this->fontSize = sqrt($this->width * $this->height) / 80;
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

            if (($this->width > $minSize) && ($this->height > $minSize)) {
                $this->generateWm($image);
            }
        }
    }

    /**
     * @param $image Image
     */
    private function generateWm($image)
    {
        $watermarkFontFile  = realpath(__DIR__.'/../web/fonts') . "/{$this->font}.ttf";
        $palette = new RGB();
        $watermarkFont = new Font($image->getImagick(), $watermarkFontFile, $this->fontSize, $palette->color($this->hexColor, $this->opacity));
        $text = $watermarkFont->box($this->text, $this->angle);

        $textWidth = $text->getWidth();
        $textHeight = $text->getHeight();

        $textOriginY = $textHeight + $this->margin;
        while (($textOriginY - $textHeight) < $image->getSize()->getHeight()) {
            $textOriginX = $this->margin;
            while ($textOriginX < $image->getSize()->getWidth()) {
                $image->draw()->text(
                    $this->text,
                    $watermarkFont,
                    new Point($textOriginX, $textOriginY),
                    $this->angle
                );
                $textOriginX += ($textWidth + $this->margin);
            }
            $textOriginY += ($textHeight + $this->margin);
        }
    }
}