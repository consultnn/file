<?php

namespace components;

use helpers\FileHelper;
use helpers\PathHelper;
use Imagick;
use Imagine\File\Loader;
use Imagine\Imagick\Imagine;
use Slim\Http\UploadedFile;

class Upload
{
    public $project;
    public $params;
    public $files;
    public $urls;
    public $pdf;
    public $token;

    public function getFiles()
    {
        $files = [];
        foreach ($this->files as $uploadedName => $uploadedFile) {
            $webPath = $this->saveFile($uploadedFile);
            $files[] = $webPath;
            if ($this->pdf) {
                list($firstDir, $secondDir, $storageName) = PathHelper::splitNameIntoParts($webPath);

                $filePath = implode(DIRECTORY_SEPARATOR, [$this->project, $firstDir, $secondDir, $storageName]);
                $this->pdf(PathHelper::resolvePhysicalPath($filePath));
            }
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
    private function saveFile($uploadedFile)
    {
        if ($this->checkErrors($uploadedFile)) {
            return false;
        }

        $extension = FileHelper::getExtension($uploadedFile->file);

        if ($this->params || ($this->params && !empty($this->params[$extension]))) {
            return PathHelper::generateWebPath($this->generateImage($uploadedFile->file, $extension), $this->project);
        }

        list($webPath, $physicalPath, $storageDir) = PathHelper::makePathData($this->project, sha1($uploadedFile->file), $extension);

        if (is_file($physicalPath)) {
            return $webPath;
        }

        PathHelper::checkDir($storageDir);
        move_uploaded_file($uploadedFile->file, $physicalPath);

        return $webPath;
    }

    /**
     * @param $file UploadedFile
     * @return bool
     */
    private function checkErrors($file)
    {
        return (!empty($file->getError()) || ($file->getSize() <= 0) || !is_uploaded_file($file->file));
    }



    private function generateImage($fileName, $extension)
    {
        $tempFile = RUNTIME_DIR . uniqid('_upload') . '.' . $extension;
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
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $tempFile = RUNTIME_DIR . uniqid('_upload') . '.' . $extension;
        file_put_contents($tempFile, $fileContent);

        $path = PathHelper::generateWebPath($tempFile, $this->project);

        if ($path) {
            unlink($tempFile);
            return $path;
        }

        list($webPath, $physicalPath, $storageDir) = PathHelper::makePathData($this->project, sha1($tempFile), $extension);

        if (is_file($physicalPath)) {
            unlink($tempFile);
            return $webPath;
        }
        PathHelper::checkDir($storageDir);
        rename($tempFile, $physicalPath);

        return $webPath;
    }

    public function pdf($uploadedFilePath)
    {
        $tmpDir = RUNTIME_DIR . microtime(true) . '_' . uniqid() . DIRECTORY_SEPARATOR;

        mkdir($tmpDir);

        $imagick = new Imagick();
        $imagick->setOption('density', 150);
        $imagick->readImage($uploadedFilePath);
        $imagick->trimImage(0);
        $imagick->setOption('sampling-factor', '4:4:4');
        $imagick->writeImages($tmpDir . '%05d.jpg', false);
        $imagick->clear();
        $imagick->destroy();
        $imageNames = array_diff(scandir($tmpDir), ['.', '..']);
        $images = [];

        foreach ($imageNames as $image) {
            $imagePath = $tmpDir . $image;
            $sha = sha1_file($imagePath);

            list($webPath, $physicalPath, $storageDir) = PathHelper::makePathData($this->project, $sha, 'jpg');

            PathHelper::checkDir($storageDir);

            rename($imagePath, $physicalPath);

            $images[]['filename'] = $webPath;
        }
        rmdir($tmpDir);
        file_put_contents(RUNTIME_DIR . 'pdf-images-' . $this->token, json_encode(['status' => 'ready', 'images' => $images]));
    }
}