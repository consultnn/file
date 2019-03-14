<?php

namespace action;

use helpers\UploadHelper;
use helpers\ZFile;
use Imagine\Exception\Exception;
use Imagine\Gd\Font;
use Imagine\Gd\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Response;

class GenerateActionMiddleware implements RequestHandlerInterface
{
    protected $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = 'gipernn';
        $file = $request->getAttribute('file');
        $hash = $request->getAttribute('hash');
        $params = $request->getAttribute('params');
        $translit = $request->getAttribute('translit');
        $extension = strtolower($request->getAttribute('extension'));
        $extension = 'jpeg';
        $hashPath = $file . '.' . $extension;

        if (ZFile::internalHash($hashPath, $params) !== $hash) {
            return $this->response->withStatus(400);
        }

        list($firstDir, $secondDir, $storageName) = UploadHelper::splitPathIntoParts($file);

        $pathPrefix = implode('/', [$project, $firstDir, $secondDir, $storageName]);
        $filePath = $pathPrefix . '.' . $extension;
        $physicalPath = $this->_resolvePhysicalPath($filePath);

        if (!$physicalPath) {
            return $this->response->withStatus(404);
        }

        $physicalExtension = strtolower(pathinfo($physicalPath, PATHINFO_EXTENSION));
        $saveName = ($translit ? $translit : $file . $params) . '.' . $extension;

        if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))
            && in_array($physicalExtension, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf'))) {
            $thumbParams = ZFile::internalDecodeParams($params);

            if ((count($thumbParams) == 0) || ((count($thumbParams) == 1) && (isset($thumbParams['wm'])) && ($thumbParams['wm'] == '0'))) {
                $mimeType = CFileHelper::getMimeTypeByExtension($saveName);
                if ($mimeType === null) {
                    $mimeType='text/plain';
                }

                header('Content-Transfer-Encoding: Binary');
                header('Content-Disposition: inline; filename="'.$saveName.'"');
                header('Content-Length: '.filesize($physicalPath));
                header('Content-Type: '.$mimeType);
                readfile($physicalPath);

                Yii::app()->end();
            }

            if ($extension !== $physicalExtension) {
                $thumbParams['f']   = $extension;
            }

            $thumbParams['sia'] = $saveName;

            $this->_generateImage($physicalPath, $thumbParams, $project)->show($extension);
        } elseif ($extension == $physicalExtension) {
            readfile($physicalPath);
            return $this->response;
        } else {
            return $this->response->withStatus(404);
        }
        var_dump($request->getAttribute('hash'));die();
    }

    /**
     * Создание и отдача изображения используя phpThumb
     * @param string $physicalPath путь к файлу-оригиналу.
     * @param array $thumbParams параметры нового изображения
     * @param string $project проект
     * @return object
     */
    private function _generateImage($physicalPath, $thumbParams, $project)
    {
        $imagine = new Imagine();

        if (!isset($thumbParams['q']) && (isset($thumbParams['w']) || isset($thumbParams['h'])))
            $thumbParams['q'] = 80;

//        $image = $imagine->open($physicalPath);
//        $size = $image->getSize()->widen($thumbParams['w'])->heighten($thumbParams['h']);
//        return $image->thumbnail(new Box($thumbParams['w'], $thumbParams['h']));
//        $width = $thumbParams['w'];
//        $height = $thumbParams['h'];
//        $thumbnail = $image->thumbnail(new Box($image->getSize()->getWidth(), $image->getSize()->getHeight()));
//$thumbnail->resize($size)->crop(new Point($width / 2 - $size->getWidth() / 2, $height / 2 - $size->getHeight() / 2), new Box($image->getSize()->getWidth(), $image->getSize()->getHeight()));
//        if ($size->getWidth() < $width or $size->getHeight() < $height) {
//            $white = $imagine->create(new Box($width, $height));
//            $thumbnail = $white->paste($thumbnail, new Point($width / 2 - $size->getWidth() / 2, $height / 2 - $size->getHeight() / 2));
//        }
//        return $thumbnail;



//        foreach($thumbParams as $param => $value) {
//            $phpThumb->setParameter($param, $value);
//        }
        $image = $imagine->open($physicalPath);
        $thumbnail = $image->thumbnail(new Box($thumbParams['w'], $thumbParams['h']), ImageInterface::THUMBNAIL_OUTBOUND);
        if (!isset($thumbParams['wm']) && ($project == 'gipernn')) {
            $thumbParams['wm'] = 'gipernn';
        }

        if (!empty($thumbParams['wm'])) {
            $size = getImageSize($physicalPath);
            $width	= (isset($thumbParams['w']) ? $thumbParams['w'] : (isset($thumbParams['h']) ? $thumbParams['h'] : 0));
            $height	= (isset($thumbParams['h']) ? $thumbParams['h'] : (isset($thumbParams['w']) ? $thumbParams['w'] : 0));

            if ($width >= $size[0] || empty($width)) {
                $width = $size[0];
            }

            if ($height >= $size[1] || empty($height)) {
                $height = $size[1];
            }

            $watermark = null;
            switch ($thumbParams['wm']) {
                case 'gipernn':
                    $minSize = 150;
                    $fontSize = sqrt($width*$height)/80;
                    $distance = $fontSize*10;
                    $watermark = "GIPERNN.RU|{$fontSize}|*|ffffff|generis.ttf|75|{$distance}|-35";
                    break;
                case 'dom':
                    $minSize = 150;
                    $fontSize = sqrt($width*$height)/45;
                    $distance = $fontSize*10;
                    $watermark = "DOMOSTROYNN.RU|{$fontSize}|*|ffffff|roboto.ttf|75|{$distance}|35";
                    break;
                case 'domdon':
                    $minSize = 150;
                    $fontSize = sqrt($width*$height)/45;
                    $distance = $fontSize*10;
                    $watermark = "DomostroyDON.ru|{$fontSize}|*|ffffff|roboto.ttf|75|{$distance}|35";
                    break;
                default:
                    $minSize = 100;
                    $watermark = $thumbParams['wm'];
            }

            if (!empty($watermark) && ($width > $minSize) && ($height > $minSize)) {
                $thumbnail = $this->generateWm($thumbnail, $watermark);
            }
        }

        return $thumbnail;
    }

    /**
     * По uri-имени возвращает путь к файлу-оригиналу или false если он не найден.
     * @param string $webPath
     * @return string|boolean
     */
    private function _resolvePhysicalPath($webPath)
    {
        $storagePath = realpath(__DIR__.'/../storage');
        $path = implode('/', [$storagePath, $webPath]);
        if (is_file($path))
            return $path;

        $pathInfo = pathinfo($webPath);
        $symlinkPath = implode('/', [$storagePath, $pathInfo['dirname'], $pathInfo['filename']]);

        if (is_link($symlinkPath))
            return readlink($symlinkPath);

        return false;
    }

    private function generateWm($photo, $parameter)
    {
        list($text, $size, $alignment, $hex_color, $ttffont, $opacity, $margin, $angle) = explode('|', $parameter);
        $text       = ($text            ? $text       : '');
        $size       = ($size            ? $size       : 3);
        $alignment  = ($alignment       ? $alignment  : 'BR');
        $hex_color  = ($hex_color       ? $hex_color  : '000000');
        $ttffont    = ($ttffont         ? $ttffont    : '');
        $opacity    = (strlen($opacity) ? $opacity    : 50);
        $margin     = (strlen($margin)  ? $margin     : 5);
        $angle      = (strlen($angle)   ? $angle      : 0);

        $watermarkFontFile  = realpath(__DIR__.'/../web/fonts') . '/' . $ttffont;

        // use black text if brightness difference is not sufficient

        $watermarkFont = new Font($watermarkFontFile, $size, new Color($hex_color, (int)$opacity));
        $TTFbox = ImageTTFbBox($size, $angle, $watermarkFontFile, $text);
        $min_x = min($TTFbox[0], $TTFbox[2], $TTFbox[4], $TTFbox[6]);
        $max_x = max($TTFbox[0], $TTFbox[2], $TTFbox[4], $TTFbox[6]);
        //$text_width = round($max_x - $min_x + ($size * 0.5));
        $text_width = round($max_x - $min_x);

        $min_y = min($TTFbox[1], $TTFbox[3], $TTFbox[5], $TTFbox[7]);
        $max_y = max($TTFbox[1], $TTFbox[3], $TTFbox[5], $TTFbox[7]);
        //$text_height = round($max_y - $min_y + ($size * 0.5));
        $text_height = round($max_y - $min_y);

        $TTFboxChar = ImageTTFbBox($size, $angle, $watermarkFontFile, 'jH');
        $char_min_y = min($TTFboxChar[1], $TTFboxChar[3], $TTFboxChar[5], $TTFboxChar[7]);
        $char_max_y = max($TTFboxChar[1], $TTFboxChar[3], $TTFboxChar[5], $TTFboxChar[7]);
        $char_height = round($char_max_y - $char_min_y);

        if ($alignment == '*') {

            $text_origin_y = $char_height + $margin;
            while (($text_origin_y - $text_height) < $photo->getSize()->getHeight()) {
                $text_origin_x = $margin;
                while ($text_origin_x < $photo->getSize()->getWidth()) {
                    $photo->draw()->text(
                        $text,
                        $watermarkFont,
                        new Point($text_origin_x, $text_origin_y),
                        $angle
                    );
                    $text_origin_x += ($text_width + $margin);
                }
                $text_origin_y += ($text_height + $margin);
            }
        }
//        var_dump($text_origin_y);die();
//        // draw watermark
//        $photo->draw()->text(
//            str_repeat($text, 2),
//            $watermarkFont,
//            $watermarkPosition,
//            $angle
//        );
        return $photo;
//        list($text, $size, $alignment, $hex_color, $ttffont, $opacity, $margin, $angle) = explode('|', $parameter);
//        $text       = ($text            ? $text       : '');
//        $size       = ($size            ? $size       : 3);
//        $alignment  = ($alignment       ? $alignment  : 'BR');
//        $hex_color  = ($hex_color       ? $hex_color  : '000000');
//        $ttffont    = ($ttffont         ? $ttffont    : '');
//        $opacity    = (strlen($opacity) ? $opacity    : 50);
//        $margin     = (strlen($margin)  ? $margin     : 5);
//        $angle      = (strlen($angle)   ? $angle      : 0);
////        var_Dump(basename($ttffont) == $ttffont);die();
////        if (basename($ttffont) == $ttffont) {
////            $ttffont = realpath($this->config_ttf_directory.DIRECTORY_SEPARATOR.$ttffont);
////        } else {
////            $ttffont = $this->ResolveFilenameToAbsolute($ttffont);
////        }
//        $phpthumbFilters->WatermarkText($this->gdimg_output, $text, $size, $alignment, $hex_color, $ttffont, $opacity, $margin, $angle, $bg_color, $bg_opacity, $fillextend);
    }

}