<?php

namespace components;

use helpers\FileHelper;
use Imagine\File\Loader;
use Laminas\Diactoros\UploadedFile;

/**
 * Class Upload
 * @property Filesystem $filesystem
 * @package components
 */
class Upload
{
    public $filesystem;
    public $params;
    public $files;
    public $urls;

    public function getFiles()
    {
        $files = [];
        foreach ($this->files as $uploadedName => $uploadedFile) {
            $files[] = $this->saveFile($uploadedFile);
        }

        if (isset($this->urls)) {
            $files = array_merge($files, $this->loadFiles($this->urls));
        }
        return $files;
    }

    /**
     * Сохраняем временный файл в хранилище проекта (storage) по пути вида "storage/project/firstDir/secondDir/filename.extension"
     * @param UploadedFile $uploadedFile
     * @return boolean|string false при ошибках, uri при успешном сохранении.
     */
    public function saveFile($uploadedFile)
    {
        if ($this->hasErrors($uploadedFile)) {
            return false;
        }

        $uri = $uploadedFile->getStream()->getMetadata('uri');
        $extension = $this->filesystem->getExtension($uri);

        if ($this->params && !empty($this->params[$extension])) {
            return $this->filesystem->generateWebPath($this->generateImage($uri, $extension));
        }

        list($webPath, $physicalPath) = $this->filesystem->makePathData(sha1_file($uri), $extension);

        if (!$this->filesystem->has($physicalPath)) {
            $this->filesystem->rename($uri, $physicalPath);
        }

        return $webPath;
    }

    private function hasErrors(UploadedFile $file): bool
    {
        if (!empty($file->getError()) || $file->getSize() <= 0) {
            return true;
        }

        $path = $file->getStream()->getMetadata('uri');

        return !is_uploaded_file($path) && (dirname($path) !== sys_get_temp_dir());
    }

    private function generateImage($fileName, $extension): array
    {
        $params = array_merge(['f' => $extension], $this->params[$extension]);
        $extension = $params['f'];
        $tempFile = RUNTIME_DIR . uniqid('_upload') . '.' . $extension;

        $image = new Image($fileName, $params, $extension);
        $image->generateImage()->save($tempFile, $image->options);

        return [
            sha1_file($tempFile),
            $tempFile,
            $image->format
        ];
    }

    private function loadFiles(array $urls): array
    {
        $urlBlocks = array_chunk($urls, 7);

        $results = [];
        foreach ($urlBlocks as $urlBlock) {
            $results = array_merge($results, $this->bulkLoad($urlBlock));
        }

        return $results;
    }

    private function bulkLoad(array $urls): array
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

    private function saveLoadedFile($url, $fileContent): string
    {
        $extension = FileHelper::getPhysicalExtension($url);
        $tempFile = RUNTIME_DIR . uniqid('_upload') . '.' . $extension;
        $this->filesystem->write($tempFile, $fileContent);

        list($webPath, $physicalPath) = $this->filesystem->makePathData(sha1_file($tempFile), $extension);

        if (!$this->filesystem->has($physicalPath)) {
            $this->filesystem->rename($tempFile, $physicalPath);
        }

        return $webPath;
    }
}
