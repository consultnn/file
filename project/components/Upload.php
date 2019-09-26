<?php

namespace components;

use helpers\FileHelper;
use Imagine\File\Loader;
use Zend\Diactoros\UploadedFile;

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
        if ($this->checkErrors($uploadedFile)) {
            return false;
        }

        $extension = FileHelper::getExtension($uploadedFile->getStream()->getMetadata('uri'));

        if (!$this->params || ($this->params && !empty($this->params[$extension]))) {
            return $this->filesystem->generateWebPath($this->generateImage($uploadedFile->getStream()->getMetadata('uri'), $extension));
        }

        list($webPath, $physicalPath, $storageDir) = $this->filesystem->makePathData(sha1_file($uploadedFile->getStream()->getMetadata('uri')), $extension);

        if ($this->filesystem->has($physicalPath)) {
            return $webPath;
        }

        $this->filesystem->createDir($storageDir);
        move_uploaded_file($uploadedFile->getStream()->getMetadata('uri'), $physicalPath);

        return $webPath;
    }

    /**
     * @param $file UploadedFile
     * @return bool
     */
    private function checkErrors($file)
    {
        return (!empty($file->getError()) || ($file->getSize() <= 0) || !is_uploaded_file($file->getStream()->getMetadata('uri')));
    }

    private function generateImage($fileName, $extension)
    {
        $tempFile = RUNTIME_DIR . uniqid('_upload') . '.' . $extension;
        $params = array_merge($this->params, ['f' => $extension]);
        $image = new Image($fileName, $params, $extension);
        $realImage = $image->generateImage()->save($tempFile, $image->options);
        return [
            sha1_file($realImage->metadata()->get('filepath')),
            $tempFile,
            $image->format
        ];
    }

    private function loadFiles($urls)
    {
        $urlBlocks = array_chunk($urls, 7);

        $results = [];
        foreach ($urlBlocks as $urlBlock) {
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
        $extension = FileHelper::getPhysicalExtension($url);
        $tempFile = RUNTIME_DIR . uniqid('_upload') . '.' . $extension;
        $this->filesystem->write($tempFile, $fileContent);

        return $this->filesystem->generateWebPath($this->generateImage($tempFile, $extension));
    }
}
