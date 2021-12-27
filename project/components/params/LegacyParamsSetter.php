<?php

namespace components\params;

use components\Image;
use Imagine\Image\Palette\RGB;

final class LegacyParamsSetter implements ParamsSetter
{
    private $_params = [];

    public function __construct(array $params)
    {
        $this->_params = $params;
    }

    public function noTransform(): bool
    {
        if (count($this->_params) == 0) {
            return true;
        }

        if ((count($this->_params) === 1)
            && array_key_exists('wm', $this->_params)
            && ($this->_params['wm'] === '0')
        ) {
            return true;
        }
        return false;
    }

    public function apply(Image $image): void
    {
        $image->quality = self::QUALITY_DEFAULT;
        if (isset($this->_params['q'])) {
            $image->quality = min($this->_params['q'], self::QUALITY_MAX);
        }
        $image->setOption('quality', $image->quality);

        if (isset($this->_params['zc'])) {
            $image->crop = $this->_params['zc'];
        }

        if (isset($this->_params['stc'])) {
            $image->setTransparentColor = (strlen($this->_params['stc']) === 6) ? '#' . $this->_params['stc'] : $this->_params['stc'];
        }

        $size = $image->sourceImage->getSize();
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
        $image->width = $this->oneOfThese($widthParams);
        $image->height = $this->oneOfThese($heightParams);

        if (isset($this->_params['far']) && empty($this->crop)) {
            $image->far = $this->_params['far'];
        }

        if (isset($this->_params['bg'])) {
            $palette = new RGB();
            if ($this->_params['bg'] === 'transparent') {
                $image->background = $palette->color('#000000', 0);
            } else {
                $image->background = $palette->color('#' . $this->_params['bg']);
            }
        }

        if (isset($this->_params['aoe'])) {
            $image->aoe = true;
        }

        if (isset($this->_params['wm']) && $this->_params['wm'] != 0) {
            $image->watermark = true;
        }

        if (isset($this->_params['ar'])) {
            $image->autoRotate = true;
        }

        $this->setFormat($image);
    }

    private function setFormat(Image $image)
    {
        if (isset($this->_params['f'])) {
            $image->format = $this->_params['f'];
        }
        $image->setOption('format', $image->format);

        /**  TODO аналогично для WebP */
        if ($image->format === self::FORMAT_PNG) {
            $image->setOption('png_compression_level', 8);
        }
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