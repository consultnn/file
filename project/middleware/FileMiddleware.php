<?php

namespace middleware;

use components\Image;
use helpers\FileHelper;
use helpers\PathHelper;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

class FileMiddleware implements RequestHandlerInterface
{
    private $response;
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
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
        $project = $request->getServerParams()['DOMAIN'];
        $file = $request->getAttribute('file');
        $hash = $request->getAttribute('hash');
        $params = $request->getAttribute('params');
        $extension = strtolower($request->getAttribute('extension'));
        $hashPath = $file . '.' . $extension;

        if (FileHelper::internalHash($hashPath, $params, $this->settings['downloadSecret']) !== $hash) {
            return $this->response->withStatus(400);
        }

        $filePath = PathHelper::makePath($file, $project);
        $physicalPath = PathHelper::resolvePhysicalPath($filePath);

        if (!$physicalPath || !is_file($physicalPath)) {
            return $this->response->withStatus(404);
        }

        $physicalExtension = FileHelper::getPhysicalExtension($physicalPath);
        list($saveDir, $fullPath, $saveName) = PathHelper::makeCachePath($filePath, $extension, $hash, $params);
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])
            && in_array($physicalExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf'])) {

            $params = FileHelper::internalDecodeParams($params);

            if ((count($params) == 0) || (count($params) == 1 && (isset($params['wm'])) && ($params['wm'] == '0'))) {
                $mimeType = FileHelper::getMimeTypeByExtension($saveName);
                readfile($physicalPath);
                return $this->response
                    ->withHeader('Content-Transfer-Encoding', 'Binary')
                    ->withHeader('Content-Disposition', "inline; filename='{$saveName}'")
                    ->withHeader('Content-Type', $mimeType);
            }

            PathHelper::checkDir($saveDir);

            $image = new Image($physicalPath, $params, $extension);
            $image->savePath = $fullPath;
            $image->project = $project;
            $image->show();
            return $this->response;
        } elseif ($extension == $physicalExtension) {
            readfile($physicalPath);
            return $this->response
                ->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Disposition', "attachment; filename='{$saveName}'")
                ->withHeader('Content-Type', 'application/pdf');
        }

        return $this->response->withStatus(404);
    }
}