<?php

namespace handlers;

use components\Upload as UploadComponent;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class Upload extends BaseHandler
{
    public $uploadSecret;

    public function handle(): ResponseInterface
    {
        if ($this->app->request->getAttribute('uploadSecret') !== $this->uploadSecret) {
            return new EmptyResponse(401);
        }

        $upload = new UploadComponent;
        $upload->filesystem = $this->app->filesystem;
        $upload->params = $this->app->request->getQueryParams()['params'] ?? [];
        $upload->files = $this->app->request->getUploadedFiles();
        $upload->urls = $this->app->request->getParsedBody()['urls'] ?? null;

        return new JsonResponse($upload->getFiles());
    }
}
