<?php

namespace components\params;

use components\Image;
use Imagine\Image\Palette\RGB;

final class LegacyParamsSetter implements ParamsSetter
{
    /** @var Image  */
    private $_image;
    private $_params = [];

    public function __construct(Image $image, array $params)
    {
        $this->_image = $image;
        $this->_params = $params;
    }

    public function apply(): void
    {
        $this->_image->quality = self::QUALITY_DEFAULT;
        if (isset($this->_params['q'])) {
            $this->_image->quality = min($this->_params['q'], self::QUALITY_MAX);
        }
        $this->setOption('quality', $this->_image->quality);

        if (isset($this->_params['zc'])) {
            $this->_image->crop = $this->_params['zc'];
        }

        if (isset($this->_params['stc'])) {
            $this->_image->setTransparentColor = (strlen($this->_params['stc']) === 6) ? '#' . $this->_params['stc'] : $this->_params['stc'];
        }

        $size = $this->_image->sourceImage->getSize();
        $ratio = $size->getWidth() / $size->getHeight();
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
        $this->_image->width = $this->oneOfThese($widthParams);
        $this->_image->height = $this->oneOfThese($heightParams);

        if (isset($this->_params['far']) && empty($this->crop)) {
            $this->_image->far = $this->_params['far'];
        }

        if (isset($this->_params['bg'])) {
            $palette = new RGB();
            if ($this->_params['bg'] === 'transparent') {
                $this->_image->background = $palette->color('#000000', 0);
            } else {
                $this->_image->background = $palette->color('#' . $this->_params['bg']);
                /** TODO подозрительная смена типа */
                $this->_image->format = self::FORMAT_JPEG;
            }
        }

        if (isset($this->_params['aoe'])) {
            $this->_image->aoe = true;
        }

        if (isset($this->_params['wm']) && $this->_params['wm'] != 0) {
            $this->_image->watermark = $this->_params['wm'];
        }

        if (isset($this->_params['ar'])) {
            $this->_image->autoRotate = true;
        }

        $this->setFormat();
    }

    private function setFormat()
    {
        if (isset($this->_params['f'])) {
            $this->_image->format = $this->_params['f'];
        }
        $this->setOption('format', $this->_image->format);

        /**  TODO аналогично для WebP */
        if ($this->_image->format === self::FORMAT_PNG) {
            $this->setOption('png_compression_level', 8);
        }
    }

    private function setOption($key, $value)
    {
        $this->_image->options[$key] = $value;
    }

    private function oneOfThese($values)
    {
        foreach ($values as $key => $value) {
            if (isset($this->_params[$value])) {
                return $this->_params[$value];
            }
        }
        return null;
    }
}