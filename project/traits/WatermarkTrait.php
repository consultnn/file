<?php

namespace traits;

use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Font;
use Imagine\Imagick\Image;

trait WatermarkTrait
{
    public $project;
    private $_text = '';
    private $_fontSize = 3;
    private $_hexColor = 'ffffff';
    private $_font = 'roboto';
    private $_opacity = 25;
    private $_margin = 5;
    private $_angle = -35;

    public function generateWatermark($image)
    {
        if (!isset($this->watermark) && ($this->project == self::GIPER)) {
            $this->watermark = self::GIPER;
        }

        if (!empty($this->watermark)) {
            $minSize = 150;
            $this->_fontSize = sqrt($this->width * $this->height) / 45;
            switch ($this->watermark) {
                case self::GIPER:
                    $this->_fontSize = sqrt($this->width * $this->height) / 80;
                    $this->_text = 'GIPERNN.RU';
                    $this->_font = 'generis';
                    break;
                case self::DOMOSTROY:
                    $this->_text = 'DOMOSTROYNN.RU';
                    break;
                case self::DOMOSTROYDON:
                    $this->_text = 'DomostroyDON.ru';
                    break;
                default:
                    $minSize = 100;
                    $this->_text = $this->watermark;
                    $this->_hexColor = '000000';
                    $this->_opacity = 50;
            }
            $this->_margin = $this->_fontSize * 10;

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
        $watermarkFontFile  = realpath(__DIR__ . '/../web/fonts') . "/{$this->_font}.ttf";
        $palette = new RGB();
        $watermarkFont = new Font($image->getImagick(), $watermarkFontFile, $this->_fontSize, $palette->color($this->_hexColor, $this->_opacity));
        $text = $watermarkFont->box($this->_text, $this->_angle);

        $textWidth = $text->getWidth();
        $textHeight = $text->getHeight();

        $textOriginY = $textHeight + $this->_margin;
        while (($textOriginY - $textHeight) < $image->getSize()->getHeight()) {
            $textOriginX = $this->_margin;
            while ($textOriginX < $image->getSize()->getWidth()) {
                $image->draw()->text(
                    $this->_text,
                    $watermarkFont,
                    new Point($textOriginX, $textOriginY),
                    $this->_angle
                );
                $textOriginX += ($textWidth + $this->_margin);
            }
            $textOriginY += ($textHeight + $this->_margin);
        }
    }
}
