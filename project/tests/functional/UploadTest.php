<?php

declare(strict_types=1);

namespace tests\functional;

use Application;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use tests\helpers\File;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\UploadedFile;
use Laminas\Diactoros\Uri;

/**
 * Class UploadTestCase
 * @package Tests\Functional
 */
class UploadTest extends TestCase
{
    public function testFailAuthenticate()
    {
        $response = $this->runApp('POST', '/upload/123/example?domain=example');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testFailConfig()
    {
        $server = ['DOMAIN' => 'example2'];
        $request = (new ServerRequest($server))
            ->withMethod('POST')
            ->withUri(new Uri('/upload/N3edBMSnQrakH9nBK98Gmmrz367JxWCT/example2?domain=example2'))
        ;

        $config = array_replace_recursive(
            require __DIR__ . '/../../settings/config.php',
            require __DIR__ . '/../config/config.php'
        );

        $application = new Application();
        $application->request = $request;

        $response = $application->run($config);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testFile()
    {
        $source = [
            'di.png' => 'r606m0z5ygvgd.png'
        ];

        $files = [];
        foreach ($source as $fileName => $saveName) {
            $tempPath = File::copyFileToTemp($fileName);
            $files[] = new UploadedFile($tempPath, filesize($tempPath), UPLOAD_ERR_OK);
        }

        $response = $this->runApp(
            'POST',
            '/upload/N3edBMSnQrakH9nBK98Gmmrz367JxWCT/example?domain=example',
            $files
        );
        $response->getBody()->rewind();
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(array_values($source), $data);
    }

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param \Psr\Http\Message\UploadedFileInterface[] $files
     * @return ResponseInterface
     */
    public function runApp(string $requestMethod, string $requestUri, $files = null)
    {
        $server = ['DOMAIN' => 'example'];
        $request = new ServerRequest($server, $files ?: [], new Uri($requestUri), $requestMethod);

        $config = array_replace_recursive(
            require __DIR__ . '/../../settings/config.php',
            require __DIR__ . '/../config/config.php'
        );

        $application = new Application();
        $application->request = $request;
        return $application->run($config);
    }
}
