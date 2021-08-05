<?php

namespace handlers;

use components\UploadPdf as UploadPdfComponent;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class UploadPdf extends BaseHandler
{
    public $uploadSecret;

    public function handle(): ResponseInterface
    {
        if ($this->app->request->getAttribute('uploadSecret') !== $this->uploadSecret) {
            return new EmptyResponse(401);
        }

        $upload = new UploadPdfComponent;
        $upload->filesystem = $this->app->filesystem;
        $upload->params = $this->app->request->getQueryParams()['params'] ?? [];
        $upload->files = $this->app->request->getUploadedFiles();
        $upload->token = $this->app->request->getQueryParams()['token'] ?? null;

        return new JsonResponse($upload->getFiles());
    }
}
