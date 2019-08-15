<?php

namespace handlers;

use components\UploadPdf as UploadPdfComponent;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

class UploadPdf extends BaseHandler
{
    public $uploadSecret;

    public function handle(): ResponseInterface
    {
        if ($this->app->request->getAttribute('uploadSecret') !== $this->uploadSecret) {
            return $this->app->response->withStatus(400);
        }

        $upload = new UploadPdfComponent;
        $upload->project = $this->app->project;
        $upload->params = $this->app->request->getQueryParams()['params'] ?? [];
        $upload->files = $this->app->request->getUploadedFiles();
        $upload->token = isset($this->app->request->getQueryParams()['token']) ? $this->app->request->getQueryParams()['token'] : null;

        return new JsonResponse($upload->getFiles());
    }
}