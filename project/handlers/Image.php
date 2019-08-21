<?php

namespace handlers;

use components\Image as ComponentImage;
use helpers\FileHelper;
use League\Flysystem\Util;
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

    private $_allowExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    private $_physicalExtension = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf'];

    public function handle(): ResponseInterface
    {
        $file = $this->app->request->getAttribute('file');
        $hash = $this->app->request->getAttribute('hash');
        $params = $this->app->request->getAttribute('params');
        $extension = strtolower($this->app->request->getAttribute('extension'));

        $hashPath = "{$file}.{$extension}";

        if (FileHelper::internalHash($hashPath, $params, $this->downloadSecret) !== $hash) {
            return $this->app->response->withStatus(400);
        }
        $filesystem = $this->app->filesystem;
        $filesystem->project = $this->app->project;
        $filesystem->fileName = $file;

        $physicalPath = $filesystem->resolvePhysicalPath();
        if (!$physicalPath || !is_file($physicalPath)) {
            return $this->app->response->withStatus(404);
        }

        $physicalExtension = FileHelper::getPhysicalExtension($physicalPath);
        $filesystem->makeCachePath($extension, $hash, $params);

        if (in_array($extension, $this->_allowExtensions) && in_array($physicalExtension, $this->_physicalExtension)) {
            $params = FileHelper::internalDecodeParams($params);

            if ((count($params) == 0) || (count($params) == 1 && (isset($params['wm'])) && ($params['wm'] == '0'))) {
                return $this->app->response->withFileHeaders($physicalPath, $filesystem->saveName);
            }

            $filesystem->createDir($filesystem->cachePath);

            $image = new ComponentImage($physicalPath, $params, $extension);
            $image->savePath = $filesystem->cacheFile;
            if ($this->app->project === 'gipernn') {
                $image->watermark = true;
            }
            $image->watermarkConfig = $this->watermark;
            $image->show();
            return $this->app->response;
        } elseif ($extension == $physicalExtension) {
            return $this->app->response->withFileHeaders($physicalPath, $filesystem->saveName, 'attachment');
        }

        return $this->app->response->withStatus(404);
    }
}