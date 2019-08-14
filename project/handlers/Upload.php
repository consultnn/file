<?php

namespace handlers;

use components\Upload as UploadComponent;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

class Upload extends BaseHandler
{
    public function handle(): ResponseInterface
    {
        if ($this->app->request->getAttribute('uploadSecret') !== $this->app->config['uploadSecret']) {
            return $this->app->response->withStatus(400);
        }

        $upload = new UploadComponent;
        $upload->project = $this->app->project;
        $upload->params = $this->app->request->getQueryParams()['params'] ?? [];
        $upload->files = $this->app->request->getUploadedFiles();
        $upload->urls = isset($this->app->request->getParsedBody()['urls']) ? $this->app->request->getParsedBody()['urls'] : null;

        return new JsonResponse($upload->getFiles());
    }
}