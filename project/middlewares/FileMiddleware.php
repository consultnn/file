<?php

namespace middlewares;

use components\Image;
use helpers\FileHelper;
use Imagine\Filter\Transformation;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Imagick\Font;
use Imagine\Imagick\Imagine;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

class FileMiddleware implements RequestHandlerInterface
{
    private $response;
    private $settings;

    public function __construct($container)
    {
        $this->settings = $container->get('settings');
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
        $translit = $request->getAttribute('translit');
        $extension = strtolower($request->getAttribute('extension'));
        $hashPath = $file . '.' . $extension;

        if (FileHelper::internalHash($hashPath, $params, $this->settings['downloadSecret']) !== $hash) {
            return $this->response->withStatus(400);
        }

        $filePath = FileHelper::makePath($file, $project, $extension);
        $physicalPath = FileHelper::resolvePhysicalPath($filePath);

        if (!$physicalPath || !is_file($physicalPath)) {
            return $this->response->withStatus(404);
        }

        $physicalExtension = FileHelper::getPhysicalExtension($physicalPath);
        $saveName = ($translit ? $translit : $file . $params) . '.' . $extension;

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])
            && in_array($physicalExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf'])) {
            $params = FileHelper::internalDecodeParams($params);

            if ((count($params) == 0) || ((count($params) == 1) && (isset($params['wm'])) && ($params['wm'] == '0'))) {
                $mimeType = FileHelper::getMimeTypeByExtension($saveName) ?? 'text/plain';

                readfile($physicalPath);

                return $this->response
                    ->withHeader('Content-Transfer-Encoding', 'Binary')
                    ->withHeader('Content-Disposition', "inline; filename='{$saveName}'")
                    ->withHeader('Content-Length', filesize($physicalPath))
                    ->withHeader('Content-Type', $mimeType);
            }
            $paths = explode('.', $filePath);
            $image = new Image($params);
            $image->path = $physicalPath;
            $image->savePath = "/www/web/cache/{$paths[0]}_{$hash}.{$extension}";
            $image->project = $project;
            if (empty($image->format)) {
                $image->format = $extension;
            }
            $image->generateImage();
            return $this->response;
        } elseif ($extension == $physicalExtension) {
            readfile($physicalPath);
            return $this->response
                ->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Length', filesize($physicalPath))
                ->withHeader('Content-Disposition', "attachment; filename='{$saveName}'")
                ->withHeader('Content-Type', 'application/pdf');
        }

        return $this->response->withStatus(404);
    }
}