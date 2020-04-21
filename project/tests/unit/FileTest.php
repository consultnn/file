<?php

namespace Tests\unit;

use components\Filesystem;
use PHPUnit\Framework\TestCase;
use Tests\helpers\File;

class FileTest extends TestCase
{
    /**
     * @dataProvider fileProvider
     */
    public function testStore(string $filename, string $saveName)
    {
        $tempFile = \Tests\helpers\File::copyFileToTemp($filename);
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
    public function testChangeFormat()
    {
        list($name, $path) = $this->upload('large.png', ['png' => ['f' => 'jpeg']]);
        $this->assertEquals('jpeg', pathinfo($name, PATHINFO_EXTENSION));
        $this->assertEquals(IMAGETYPE_JPEG, exif_imagetype($path));
    }

    /**
     * @depends testStore
     */
    public function testChangeSize()
    {
        list($name, $path) = $this->upload('large.png', ['png' => ['w' => 100]]);
        $this->assertEquals('png', pathinfo($name, PATHINFO_EXTENSION));

        $info = getimagesize($path);
        $this->assertEquals($info[0], 100);
    }

    private function upload(string $fileName, array $params): array
    {
        $tempFile = \Tests\helpers\File::copyFileToTemp($fileName);

        $fileSystem = new Filesystem(['project' => 'example']);
        $name = File::upload($tempFile, $fileSystem, $params);
        $fileSystem->fileName = $name;

        return [
            $name,
            $fileSystem->resolvePhysicalPath()
        ];
    }
}
