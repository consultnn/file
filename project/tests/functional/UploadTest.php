<?php

declare(strict_types=1);

namespace Tests\functional;

use Application;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Tests\helpers\File;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\UploadedFile;
use Zend\Diactoros\Uri;

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
        $response = $this->runApp('POST', '/upload/N3edBMSnQrakH9nBK98Gmmrz367JxWCT/example2?domain=example2');

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testFile()
    {
        $source = [
            'di.png' => 'r606m0z5ygvgd.png'
        ];
        $files = File::moveFilesToTemp(array_keys($source));

        foreach ($files as $key => $name) {
            $files[$key] = new UploadedFile($name, filesize($name), UPLOAD_ERR_OK);
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
        $request = new ServerRequest($server, $files ?: []);
        $request = $request->withMethod($requestMethod);
        $request = $request->withUri(new Uri($requestUri));

        $config = array_merge(
            require __DIR__ . '/../../settings/config.php',
            require __DIR__ . '/../config/config.php'
        );

        $application = new Application();
        $application->request = $request;
        return $application->run($config);
    }
}
