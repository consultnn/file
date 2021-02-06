<?php

namespace handlers;

use components\Image as ComponentImage;
use helpers\FileHelper;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Image
 * @property string $downloadSecret
 * @property array $_extension
 * @property array $_physicalExtension
 * @property array $watermark
 * @package handlers
 */
class Image extends BaseHandler
{
    public $downloadSecret;
    public $watermark;

    private $_allowExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $_physicalExtension = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'];

    public function handle(): ResponseInterface
    {
        $file = $this->app->request->getAttribute('file');
        $hash = $this->app->request->getAttribute('hash');
        $params = $this->app->request->getAttribute('params');
        $extension = strtolower($this->app->request->getAttribute('extension'));
        /** TODO если браузер поддерживает WebP - предпочитать его */
        
        $name = $this->app->request->getAttribute('translit') ?: $file;
        $title = pathinfo($name, PATHINFO_FILENAME) . '.' . $extension;
    
        $hashPath = "{$file}.{$extension}";

        if (FileHelper::internalHash($hashPath, $params, $this->downloadSecret) !== $hash) {
            return $this->app->response->withStatus(401);
        }
        $filesystem = $this->app->filesystem;
        $filesystem->fileName = $file;
        
        $physicalPath = $filesystem->resolvePhysicalPath();
        if (!$physicalPath || !is_file($physicalPath)) {
            return $this->app->response->withStatus(404);
        }

        $physicalExtension = FileHelper::getPhysicalExtension($physicalPath);
        $filesystem->makeCachePath($extension, $hash, $params);

        if (in_array($extension, $this->_allowExtensions)
            && in_array($physicalExtension, $this->_physicalExtension)
        ) {
            $params = FileHelper::internalDecodeParams($params);
            /** TODO проверку, нужна ли перекодировка вынести в ComponentImage */
            if (($extension === $physicalExtension)
                && ((count($params) === 0)
                    || (count($params) === 1 && (isset($params['wm'])) && ($params['wm'] === '0'))
                )
            ) {
                return $this->app->response->withFileHeaders(new Stream($physicalPath), $title);
            }

            $filesystem->createDir($filesystem->cachePath);
            
            $image = new ComponentImage($physicalPath, $params, $extension);
            $image->savePath = $filesystem->cacheFile;
            if ($this->app->project === 'gipernn' && !isset($params['wm'])) {
                $image->watermark = true;
            }
            $image->watermarkConfig = $this->watermark;
            return $this->app->response->withFileHeaders($image->show(), $title);
        } elseif ($extension === $physicalExtension) {
            return $this->app->response->withFileHeaders(new Stream($physicalPath), $title, 'attachment');
        }

        return $this->app->response->withStatus(422);
    }
}
