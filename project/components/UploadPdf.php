<?php

namespace components;

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
            $this->filesystem->fileName = $webPath;
            $this->pdf();
        }

        return $files;
    }

    public function pdf()
    {
        $tmpDir = RUNTIME_DIR . microtime(true) . '_' . uniqid() . DIRECTORY_SEPARATOR;

        $this->filesystem->createDir($tmpDir);

        $imagick = new Imagick();
        $imagick->setOption('density', 150);
        $imagick->readImage($this->filesystem->resolvePhysicalPath());
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

            list($webPath, $physicalPath) = $this->filesystem->makePathData($sha, 'jpg');

            if (!$this->filesystem->has($physicalPath)) {
                $this->filesystem->rename($imagePath, $physicalPath);
            }
            $images[]['filename'] = $webPath;
        }
        $this->filesystem->deleteDir($tmpDir);
        $this->filesystem->write(RUNTIME_DIR . 'pdf-images-' . $this->token, json_encode(['status' => 'ready', 'images' => $images]));
    }
}
