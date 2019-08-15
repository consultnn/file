<?php

namespace handlers;

use components\Image as ComponentImage;
use helpers\FileHelper;
use helpers\PathHelper;
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

        $filePath = PathHelper::makePath($file, $this->app->project);
        $physicalPath = PathHelper::resolvePhysicalPath($filePath);

        if (!$physicalPath || !is_file($physicalPath)) {
            return $this->app->response->withStatus(404);
        }

        $physicalExtension = FileHelper::getPhysicalExtension($physicalPath);

        list($saveDir, $fullPath, $saveName) = PathHelper::makeCachePath($filePath, $extension, $hash, $params);
        if (in_array($extension, $this->_allowExtensions) && in_array($physicalExtension, $this->_physicalExtension)) {
            $params = FileHelper::internalDecodeParams($params);

            if ((count($params) == 0) || (count($params) == 1 && (isset($params['wm'])) && ($params['wm'] == '0'))) {
                $mimeType = FileHelper::getMimeTypeByExtension($saveName);
                readfile($physicalPath);
                return $this->app->response
                    ->withHeader('Content-Transfer-Encoding', 'Binary')
                    ->withHeader('Content-Disposition', "inline; filename='{$saveName}'")
                    ->withHeader('Content-Type', $mimeType);
            }

            PathHelper::checkDir($saveDir);

            $image = new ComponentImage($physicalPath, $params, $extension);
            $image->savePath = $fullPath;
            if ($this->app->project === 'gipernn') {
                $image->watermark = true;
            }
            $image->watermarkConfig = $this->watermark;
            $image->show();
            return $this->app->response;
        } elseif ($extension == $physicalExtension) {
            readfile($physicalPath);
            return $this->app->response
                ->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Disposition', "attachment; filename='{$saveName}'")
                ->withHeader('Content-Type', 'application/pdf');
        }

        return $this->app->response->withStatus(404);
    }
}
