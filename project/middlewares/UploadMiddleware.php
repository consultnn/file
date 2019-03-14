<?php

namespace middlewares;

use helpers\UploadHelper;
use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

class UploadMiddleware implements RequestHandlerInterface
{
    private $response;
    private $settings;
    private $_allowMimeExtensions = [
        'svg',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'pdf',
        'text/html' => 'svg',
    ];

    public function __construct($container) {
        $this->settings = $container->get('settings');
        $this->response = new Response();
    }
    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getAttribute('uploadSecret') !== $this->settings['uploadSecret']) {
            return $this->response->withStatus(400);
        }

        $project = $request->getAttribute('project');
        $params = $request->getAttribute('params') ?? [];
        $files = [];

        foreach ($request->getUploadedFiles() as $uploadedName => $uploadedFile) {
            $webPath = $this->_saveFile($uploadedFile, $project, $params);
            $files[] = $webPath;
        }
        $urls = isset($request->getParsedBody()['urls']) ?? null;
        if ($urls) {
            $files = array_merge($files, $this->_loadFiles($urls, $project, $params));
        }

        return $this->response->withJson($files);
    }

    private function _loadFiles($urls, $project, array $params)
    {
        $urlBlocks = array_chunk($urls, 7);

        $results = [];
        foreach ($urlBlocks as $urlBlock)
        {
            $results = array_merge($results, $this->_bulkLoad($urlBlock, $project, $params));
        }

        return $results;
    }

    private function _bulkLoad($urls, $project, array $params)
    {
        $multi = curl_multi_init();

        $handles = [];
        foreach ($urls as $url)
        {
            $ch = curl_init();
            curl_setopt_array($ch, array (
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $url,
                CURLOPT_HEADER         => FALSE,
                CURLOPT_USERAGENT      => 'gipernn.ru grabber',
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 1,
            ));

            $handles[$url] = $ch;
            curl_multi_add_handle($multi, $ch);
        }

        $running = count($urls);
        do
        {
            usleep(25000);
            $res = curl_multi_exec($multi, $running);
        } while (($running > 0) || ($res == CURLM_CALL_MULTI_PERFORM));

        $results = [];

        foreach ($handles as $url => $handle)
        {
            $fileContent = (string)curl_multi_getcontent($handle);

            if (empty($fileContent) || (curl_getinfo($handle, CURLINFO_HTTP_CODE) >= 400))
            {
                $results[$url] = false;
            }
            else
            {
                $results[$url] = $this->_saveLoadedFile($url, $fileContent, $project, $params);
            }

            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }

        curl_multi_close($multi);

        return $results;
    }

    private function _saveLoadedFile($url, $fileContent, $project, array $params)
    {
        $tempFile = 'application/runtime'.DIRECTORY_SEPARATOR.uniqid('_upload').pathinfo($url, PATHINFO_EXTENSION);
        file_put_contents($tempFile, $fileContent);

        $extension = $this->_getExtension($tempFile, basename($url));

        if ($params && !empty($params[$extension])) {
            $this->_generateImage($tempFile, $params[$extension], $project)->RenderToFile($tempFile);

            $extension = $this->_getExtension($tempFile, basename($url));
            $sha = sha1_file($tempFile);
        }
        else {
            $sha = sha1($fileContent);
        }

        list($webPath, $physicalPath, $storageDir, $storageName) = UploadHelper::makeNewFileName($sha, $extension, $project);

        if (is_file($physicalPath)) {
            unlink($tempFile);
            return $webPath;
        }

        if (!is_dir($storageDir))
            mkdir($storageDir, 0775, true);

        rename($tempFile, $physicalPath);

        if (!is_link($storageDir.$storageName))
            symlink($physicalPath, $storageDir.$storageName);

        return $webPath;
    }

    /**
     * Сохраняем временный файл в хранилище проекта (storage) по пути вида "storage/project/firstDir/secondDir/filename.extension"
     * Для упрощения конвертации также создаётся символьная ссылка "storage/project/firstDir/secondDir/filename" на файл.
     * @param string $uploadedName имя загруженного файла.
     * @param UploadedFile $uploadedFile
     * @param string $project проект
     * @param array $params
     * @return boolean|string false при ошибках, uri при успешном сохранении.
     */
    private function _saveFile($uploadedFile, $project, array $params)
    {
        if (!empty($uploadedFile->getError())
            || ($uploadedFile->getSize() <= 0)
            || !is_uploaded_file($uploadedFile->file))
        {
            return false;
        }

        $extension = $this->_getExtension($uploadedFile);

        if ($params && !empty($params[$extension])) {
            $this->_generateImage($uploadedFile['tmp_name'], $params[$extension], $project)->RenderToFile($uploadedFile['tmp_name']);

            $extension = $this->_getExtension($uploadedFile);
        }

        $sha = sha1_file($uploadedFile->file);

        list($webPath, $physicalPath, $storageDir, $storageName) = UploadHelper::makeNewFileName($sha, $extension, $project);

        if (is_file($physicalPath)) {
            return $webPath;
        }

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        move_uploaded_file($uploadedFile->file, $physicalPath);

//        if (!is_link($storageDir . $storageName)) {
//            symlink($physicalPath, $storageDir . $storageName);
//        }

        return $webPath;
    }

    /**
     * Возвращает расширение файла по переданному mime-типу или содержимому файла.
     * @param UploadedFile $file
     * @return string|boolean
     */
    private function _getExtension($file)
    {
        $mime = $file->getClientMediaType();
        $fileName = $file->getClientFilename() ?? $file->file;
        $fileExtension = pathinfo($fileName);

        if ($mime) {
            if (isset($this->_allowMimeExtensions[$mime]) && $this->_allowMimeExtensions[$mime] === $fileExtension) {
                return $fileExtension;
            }
            $keyExtension = array_search($fileExtension, $this->_allowMimeExtensions, true);
            if (is_int($keyExtension) && $mime === mime_content_type($fileName)) {
                return $fileExtension;
            }
            return explode('/', $mime)[1];
        }

        $imageInfo = getimagesize($file->file);

        if (isset($imageInfo['mime'])) {
            $extension = explode('/', $imageInfo['mime'])[1];
            return ($extension == 'jpeg' ? 'jpg' : $extension);
        }

        if (isset($fileExtension)) {
            return $fileExtension;
        }

        return false;
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
        return $photo;
    }
}