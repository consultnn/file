<?php

namespace middlewares;

use helpers\UploadHelper;
use helpers\ZFile;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

class GenerateMiddleware implements MiddlewareInterface
{
    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     */
//    public function handle(ServerRequestInterface $request): ResponseInterface
//    {

//        $file = $request->getAttribute('file');
//        $hash = $request->getAttribute('hash');
//        $params = $request->getAttribute('params');
//        $translit = $request->getAttribute('translit');
//        $extension = 'jpeg';
////        $project = $request->getServerParams()['HTTP_HOST'];
//        $project = 'gipernn';
//        $hashPath = $file . '.' . $extension;
//        list($firstDir, $secondDir, $storageName) = UploadHelper::splitPathIntoParts($file);
//        $pathPrefix = implode('/', [$project, $firstDir, $secondDir, $storageName]);
//        $filePath = $pathPrefix . '.' . $extension;
//
//        $response = new Response;
//        if (ZFile::internalHash($hashPath, $params) !== $hash)
//        {
//            $response->withStatus(400);
//            return $response;
//        }
//
//        $extension = strtolower($extension);
//        $physicalPath = $this->_resolvePhysicalPath($filePath);
//        if (!$physicalPath)
//        {
//            $response->withStatus(404);
//            return $response;
//        }
//        $physicalExtension = strtolower(pathinfo($physicalPath, PATHINFO_EXTENSION));
//        $saveName = ($translit ? $translit : $file . $params).'.'.$extension;
//
//        if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))
//            && in_array($physicalExtension, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf')))
//        {
//            $thumbParams = ZFile::internalDecodeParams($params);
//            if ((count($thumbParams) == 0)
//                || ((count($thumbParams) == 1) && (isset($thumbParams['wm'])) && ($thumbParams['wm'] == '0'))
//            ) {
//                var_Dump($request->getParsedBody());die();
//                $mimeType = CFileHelper::getMimeTypeByExtension($saveName);
//                if ($mimeType === null) {
//                    $mimeType='text/plain';
//                }
//
//                header('Content-Transfer-Encoding: Binary');
//                header('Content-Disposition: inline; filename="'.$saveName.'"');
//                header('Content-Length: '.filesize($physicalPath));
//                header('Content-Type: '.$mimeType);
//                readfile($physicalPath);
//
//                Yii::app()->end();
//            }
//
//            if ($extension !== $physicalExtension) {
//                $thumbParams['f']   = $extension;
//            }
//
//            $thumbParams['sia'] = $saveName;
//            $this->_generateImage($physicalPath, $thumbParams, $project)->show('jpeg');
//        }
//
//        return $response;
//    }

    /**
     * По uri-имени возвращает путь к файлу-оригиналу или false если он не найден.
     * @param string $webPath
     * @return string|boolean
     */
    private function _resolvePhysicalPath($webPath)
    {
        $storagePath = realpath(__DIR__.'/../storage/');

        if (is_file($storagePath . $webPath))
            return $storagePath . $webPath;

        $pathInfo = pathinfo($webPath);
        $symlinkPath = $storagePath . '/' . $pathInfo['dirname'] . '/' . $pathInfo['filename'];

        if (is_link($symlinkPath))
            return readlink($symlinkPath);

        return false;
    }

    /**
     * Создание и отдача изображения используя phpThumb
     * @param string $physicalPath путь к файлу-оригиналу.
     * @param array $thumbParams параметры нового изображения
     * @param string $project проект
     * @return object
     */
//    private function _generateImage($physicalPath, $thumbParams, $project)
//    {
//        $imagine = new Imagine();
////        $phpThumb->config_disable_debug = true;
////        $phpThumb->config_temp_directory = Yii::getPathOfAlias('application.runtime');
////        $phpThumb->config_imagemagick_path = '/usr/bin/convert';
////        $phpThumb->config_allow_src_above_docroot = true;
//        $image = $imagine->open($physicalPath);
//        $size = $image->getSize()->widen($thumbParams['w'])->heighten($thumbParams['h']);
//        $width = $thumbParams['w'];
//        $height = $thumbParams['h'];
//        $thumbnail = $image->thumbnail(new Box($image->getSize()->getWidth(), $image->getSize()->getHeight()));
//$thumbnail->resize($size)->crop(new Point($width / 2 - $size->getWidth() / 2, $height / 2 - $size->getHeight() / 2), new Box($image->getSize()->getWidth(), $image->getSize()->getHeight()));
////        if ($size->getWidth() < $width or $size->getHeight() < $height) {
////            $white = $imagine->create(new Box($width, $height));
////            $thumbnail = $white->paste($thumbnail, new Point($width / 2 - $size->getWidth() / 2, $height / 2 - $size->getHeight() / 2));
////        }
////        return $thumbnail;
//
//
////        $image->crop(new Point($cropX, $cropY), new Box($width, $height));
//
////        $phpThumb->setSourceFilename($physicalPath);
////
////        if (!isset($thumbParams['q']) && (isset($thumbParams['w']) || isset($thumbParams['h'])))
////            $thumbParams['q'] = 80;
////
////        foreach($thumbParams as $param => $value)
////        {
////            $phpThumb->setParameter($param, $value);
////        }
////
////        if (!isset($thumbParams['wm']) && ($project == 'gipernn')) {
////            $thumbParams['wm'] = 'gipernn';
////        }
////
////        if (!empty($thumbParams['wm'])) {
////            $size = getImageSize($physicalPath);
////            $width	= (isset($thumbParams['w']) ? $thumbParams['w'] : (isset($thumbParams['h']) ? $thumbParams['h'] : 0));
////            $height	= (isset($thumbParams['h']) ? $thumbParams['h'] : (isset($thumbParams['w']) ? $thumbParams['w'] : 0));
////
////            if ($width >= $size[0] || empty($width)) {
////                $width = $size[0];
////            }
////
////            if ($height >= $size[1] || empty($height)) {
////                $height = $size[1];
////            }
////
////            $watermark = null;
////            switch ($thumbParams['wm']) {
////                case 'gipernn':
////                    $minSize = 150;
////                    $fontSize = sqrt($width*$height)/80;
////                    $distance = $fontSize*10;
////                    $watermark = "wmt|GIPERNN.RU|{$fontSize}|*|ffffff|generis.ttf|25|{$distance}|35";
////                    break;
////                case 'dom':
////                    $minSize = 150;
////                    $fontSize = sqrt($width*$height)/45;
////                    $distance = $fontSize*10;
////                    $watermark = "wmt|DOMOSTROYNN.RU|{$fontSize}|*|ffffff|roboto.ttf|75|{$distance}|35";
////                    break;
////                case 'domdon':
////                    $minSize = 150;
////                    $fontSize = sqrt($width*$height)/45;
////                    $distance = $fontSize*10;
////                    $watermark = "wmt|DomostroyDON.ru|{$fontSize}|*|ffffff|roboto.ttf|75|{$distance}|35";
////                    break;
////                default:
////                    $minSize = 100;
////                    $watermark = $thumbParams['wm'];
////            }
////
////            if (!empty($watermark) && ($width > $minSize) && ($height > $minSize)) {
////                $phpThumb->fltr[] = $watermark;
////            }
////        }
////
////        $phpThumb->GenerateThumbnail();
//
//        return $image;
//    }
/**
 * Process an incoming server request.
 *
 * Processes an incoming server request in order to produce a response.
 * If unable to produce the response itself, it may delegate to the provided
 * request handler to do so.
 */
//    public function handle(ServerRequestInterface $request): ResponseInterface
//    {
//        $params = $request->getAttribute('params');
//        $file = $request->getAttribute('file');
//        $hash = $request->getAttribute('hash');
//        $extension = 'jpeg';
//        $hashPath = $file . '.' . $extension;
//        $response = new Response;
////        if (ZFile::internalHash($hashPath, $params) !== $hash) {
////
////            return $response->withStatus(400);
////        }
//        return $request->ha;
//    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = $request->getAttribute('params');
        $file = $request->getAttribute('file');
        $hash = $request->getAttribute('hash');
        $extension = 'jpeg';
        $hashPath = $file . '.' . $extension;
//        if (ZFile::internalHash($hashPath, $params) !== $hash) {
//
//            return $response->withStatus(400);
//        }
        var_dump($hash);
        echo ' GenerateMW ';

        return $handler->handle($request);

    }
}