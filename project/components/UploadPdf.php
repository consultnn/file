<?php

namespace components;

use helpers\PathHelper;
use Imagick;

class UploadPdf extends Upload
{
    public $token;
    public function getFiles()
    {
        $files = [];

        foreach ($this->files as $uploadedName => $uploadedFile) {
            $webPath = $this->saveFile($uploadedFile);
            $files[] = $webPath;

            list($firstDir, $secondDir, $storageName) = PathHelper::splitNameIntoParts($webPath);

            $filePath = implode(DIRECTORY_SEPARATOR, [$this->project, $firstDir, $secondDir, $storageName]);
            $this->pdf(PathHelper::resolvePhysicalPath($filePath));
        }

        return $files;
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
