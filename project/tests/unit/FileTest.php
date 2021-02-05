<?php

namespace tests\unit;

use components\Filesystem;
use components\Image;
use PHPUnit\Framework\TestCase;
use tests\helpers\File;

class FileTest extends TestCase
{
    /**
     * @dataProvider fileProvider
     */
    public function testStore(string $filename, string $saveName)
    {
        $tempFile = \tests\helpers\File::copyFileToTemp($filename);
        $sha = sha1_file($tempFile);

        $fileSystem = new Filesystem(['project' => 'example']);
        $name = File::upload($tempFile, $fileSystem);
        $this->assertEquals($saveName, $name, 'Файл сохранён с неправильным названием');
        $this->assertEquals($sha, sha1_file($fileSystem->resolvePhysicalPath()), 'Файл пережат без нужды');
    }

    /**
     * @return array
     */
    public function fileProvider()
    {
        return [
            ['8307331.jpe', 'a2s11i90y2ixk.jpeg'], //должны исправить расширение
            ['di.png', 'r606m0z5ygvgd.png'],
            ['napoleon for svg 1.svg', 'fzlohhh132w6i.svg'],
            ['свободу сократу.png', 'q4g7g0h98m9t8.png'],
            ['csv.csv', 'hq3xtgyd9hfag.csv'],
            ['test.xlsx', 't1uoz7dotwlqm.xlsx'],
            ['test.zip', 'd98jxm20rkmud.zip'],
            ['same.tgz', '5yo60nkeiclcc.tgz'],
            ['large.jpeg', 'qdarnnyoo775y.jpeg'],
        ];
    }

    /**
     * @depends testStore
     */
    public function testChangeFormatPngToJpeg()
    {
        list($name, $path) = $this->upload('свободу сократу.png', ['png' => ['f' => 'jpeg']]);
        $this->assertEquals('jpeg', pathinfo($name, PATHINFO_EXTENSION));
        $this->assertEquals(IMAGETYPE_JPEG, exif_imagetype($path));
    }

    /**
     * @depends testStore
     */
    public function testChangeFormatPngToWebp()
    {
        list($name, $path) = $this->upload('свободу сократу.png', ['png' => ['f' => 'webp']]);
        $this->assertEquals('webp', pathinfo($name, PATHINFO_EXTENSION));
        $this->assertEquals(IMAGETYPE_WEBP, exif_imagetype($path));
    }

    /**
     * @depends testStore
     */
    public function testChangeSize()
    {
        list($name, $path) = $this->upload('свободу сократу.png', ['png' => ['w' => 100]]);
        $this->assertEquals('png', pathinfo($name, PATHINFO_EXTENSION));

        $info = getimagesize($path);
        $this->assertEquals($info[0], 100);
    }

    /**
     * @depends testStore
     */
    public function testWatermark()
    {
        $tempFile = \tests\helpers\File::copyFileToTemp('свободу сократу.png');
        $image = new Image($tempFile, ['wm' => true, 'w' => 500, 'aoe' => 1], pathinfo($tempFile, PATHINFO_EXTENSION));
        $this->assertEquals(true, $image->watermark);

        $image->watermarkConfig = [
            'text' => 'GIPERNN.RU',
            'fontSizeCoefficient' => 80,
            'hexColor' => 'ffffff',
            'font' => 'generis',
            'opacity' => 25,
            'angle' => -35,
            'minSize' => 150,
            'marginCoefficient' => 10
        ];

        foreach ([-35, 0, 35] as $angle) {
            $image->watermarkConfig['angle'] = $angle;
            $imageContent = $image->generateImage()->get('png');
            file_put_contents(RUNTIME_DIR . "test{$angle}.png", $imageContent);
        }
    }

    private function upload(string $fileName, array $params): array
    {
        $tempFile = \tests\helpers\File::copyFileToTemp($fileName);

        $fileSystem = new Filesystem(['project' => 'example']);
        $name = File::upload($tempFile, $fileSystem, $params);
        $fileSystem->fileName = $name;

        return [
            $name,
            $fileSystem->resolvePhysicalPath()
        ];
    }
}
