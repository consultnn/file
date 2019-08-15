<?php

namespace handlers;

use components\Image;
use helpers\FileHelper;
use helpers\PathHelper;
use Psr\Http\Message\ResponseInterface;

class File extends BaseHandler
{
    public $downloadSecret;

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
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])
            && in_array($physicalExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf'])) {

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

            $image = new Image($physicalPath, $params, $extension);
            $image->savePath = $fullPath;
            $image->project = $this->app->project;
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