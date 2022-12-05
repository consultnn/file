<?php

namespace handlers;

use components\Image as ComponentImage;
use components\params\LegacyParamsSetter;
use FileResponse;
use helpers\FileHelper;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Image
 * @package handlers
 */
class Image extends BaseHandler
{
    /** @var string */
    public $downloadSecret;
    /** @var string */
    public $watermark;

    private const TARGET_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const SOURCE_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'];

    public function handle(): ResponseInterface
    {
        $file = $this->app->request->getAttribute('file');
        $hash = $this->app->request->getAttribute('hash');
        $params = $this->app->request->getAttribute('params');
        $extension = strtolower($this->app->request->getAttribute('extension'));
        
        $name = $this->app->request->getAttribute('translit') ?: $file;
        $title = pathinfo($name, PATHINFO_FILENAME) . '.' . $extension;
    
        $hashPath = "{$file}.{$extension}";

        if (FileHelper::internalHash($hashPath, $params, $this->downloadSecret) !== $hash) {
            return new EmptyResponse(401);
        }
        $filesystem = $this->app->filesystem;
        $filesystem->fileName = $file;
        
        $physicalPath = $filesystem->resolvePhysicalPath();
        if (!$filesystem->fileExists($physicalPath)) {
            return new EmptyResponse(404);
        }

        $physicalExtension = FileHelper::getPhysicalExtension($physicalPath);
        $filesystem->makeCachePath($extension, $hash, $params);

        if (in_array($extension, self::TARGET_IMAGE_EXTENSIONS)
            && in_array($physicalExtension, self::SOURCE_IMAGE_EXTENSIONS)
        ) {
            $params = FileHelper::internalDecodeParams($params);
            if ($this->app->project === 'gipernn'
                && !array_key_exists('wm', $params)
            ) {
                /** TODO тест на добавление водяных знаков */
                $params['wm'] = true;
            }

            $paramsSetter = new LegacyParamsSetter($params);
            if (($extension === $physicalExtension)
                && $paramsSetter->noTransform()
            ) {
                return new FileResponse(new Stream($physicalPath), $title);
            }

            $filesystem->createDirectory($filesystem->cachePath);
            
            $image = new ComponentImage($physicalPath, $extension);
            $paramsSetter->apply($image);
            $image->savePath = $filesystem->cacheFile;
            $image->watermarkConfig = $this->watermark;
            return new FileResponse($image->show(), $title);
        }
        if ($extension === $physicalExtension) {
            return new FileResponse(new Stream($physicalPath), $title, 'attachment');
        }

        return new EmptyResponse(422);
    }
}
