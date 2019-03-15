<?php

namespace middlewares;

use components\Image;
use helpers\FileHelper;
use Imagine\File\Loader;
use Imagine\Imagick\Imagine;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

class UploadMiddleware implements RequestHandlerInterface
{
    private $response;
    private $settings;
    private $project;
    private $params;

    public function __construct(ContainerInterface $container) {
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

        $this->project = $request->getAttribute('project');
        $this->params = $request->getQueryParams()['params'] ?? [];
        $files = [];

        foreach ($request->getUploadedFiles() as $uploadedName => $uploadedFile) {
            $files[] = $this->saveFile($uploadedFile);
        }

        if (isset($request->getParsedBody()['urls'])) {
            $files = array_merge($files, $this->loadFiles($request->getParsedBody()['urls']));
        }

        return $this->response->withJson($files);
    }

    private function loadFiles($urls)
    {
        $urlBlocks = array_chunk($urls, 7);

        $results = [];
        foreach ($urlBlocks as $urlBlock)
        {
            $results = array_merge($results, $this->bulkLoad($urlBlock));
        }

        return $results;
    }

    private function bulkLoad($urls)
    {
        $results = [];
        foreach ($urls as $url) {
            $loader = new Loader($url);
            $data = $loader->getData();
            if (empty($data)) {
                $results[$url] = false;
            }
            $results[$url] = $this->saveLoadedFile($url, $data);
        }
        return $results;
    }

    private function saveLoadedFile($url, $fileContent)
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $tempFile = '/www/runtime/' . uniqid('_upload') . '.' . $extension;
        file_put_contents($tempFile, $fileContent);

        $path = $this->generateWebPath($tempFile, $extension);

        if ($path) {
            unlink($tempFile);
            return $path;
        }

        list($webPath, $physicalPath, $storageDir) = $this->makePathData(sha1($tempFile), $extension);

        if (is_file($physicalPath)) {
            unlink($tempFile);
            return $webPath;
        }

        if (!is_dir($storageDir))
            mkdir($storageDir, 0775, true);

        rename($tempFile, $physicalPath);

        return $webPath;
    }

    /**
     * Сохраняем временный файл в хранилище проекта (storage) по пути вида "storage/project/firstDir/secondDir/filename.extension"
     * @param UploadedFile $uploadedFile
     * @return boolean|string false при ошибках, uri при успешном сохранении.
     */
    private function saveFile($uploadedFile)
    {
        if (!empty($uploadedFile->getError())
            || ($uploadedFile->getSize() <= 0)
            || !is_uploaded_file($uploadedFile->file))
        {
            return false;
        }

        $extension = FileHelper::getExtension($uploadedFile->file);

        $path = $this->generateWebPath($uploadedFile->file, $extension);

        if ($path) {
            return $path;
        }

        list($webPath, $physicalPath, $storageDir) = $this->makePathData(sha1($uploadedFile->file), $extension);

        if (is_file($physicalPath)) {
            return $webPath;
        }

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        move_uploaded_file($uploadedFile->file, $physicalPath);

        return $webPath;
    }

    private function generateWebPath($fileName, $extension)
    {
        if (!$this->params || ($this->params && empty($this->params[$extension]))) {
            return false;
        }

        list($sha, $tempFile, $extension) = $this->generateImage($fileName, $extension);
        list($webPath, $physicalPath, $storageDir) = $this->makePathData($sha, $extension);

        if (is_file($physicalPath)) {
            unlink($tempFile);
            return $webPath;
        }

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }
        rename($tempFile, $physicalPath);

        return $webPath;
    }

    private function generateImage($fileName, $extension)
    {
        $tempFile = '/www/runtime/' . uniqid('_upload') . '.' . $extension;
        $image = new Image($this->params[$extension]);
        $image->format = $image->format ?? $extension;
        $imagine = new Imagine();
        $image->image = $imagine->open($fileName);
        $realImage = $image->generateImage()->save($tempFile);
        return [
            sha1_file($realImage->metadata()->get('filepath')),
            $tempFile,
            $image->format
        ];
    }

    /**
     * Make file info for save
     *
     * @param string $fileName
     * @return array
     */
    private function makePathData($sha, $extension)
    {
        static $nameLength = 13;
        static $shaOffset = 0;

        $shaBase36 = FileHelper::internalBaseConvert($sha, 16, 36);
        $webName   = substr($shaBase36, $shaOffset, $nameLength);

        if (strlen($webName) < $nameLength) {
            $webName = str_pad($webName, $nameLength, '0', STR_PAD_LEFT);
        }

        $fileDirPath = STORAGE_DIR . DIRECTORY_SEPARATOR . $this->project;
        $fileParts = FileHelper::splitNameIntoParts($webName);
        $fileName = end($fileParts);
        unset($fileParts[count($fileParts) - 1]);

        foreach ($fileParts as $partItem) {
            $fileDirPath .= DIRECTORY_SEPARATOR . $partItem;
        }

        $fileAbsolutePath = $fileDirPath . DIRECTORY_SEPARATOR . $fileName . '.' . $extension;
        $webName = $webName . '.' . $extension;

        return [
            $webName,
            $fileAbsolutePath,
            $fileDirPath
        ];
    }
}