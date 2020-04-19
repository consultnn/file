<?php

namespace handlers;

use components\Upload as UploadComponent;
use Psr\Http\Message\ResponseInterface;

class Upload extends BaseHandler
{
    public $uploadSecret;

    public function handle(): ResponseInterface
    {
        if ($this->app->request->getAttribute('uploadSecret') !== $this->uploadSecret) {
            return $this->app->response->withStatus(401);
        }

        $upload = new UploadComponent;
        $upload->filesystem = $this->app->filesystem;
        $upload->params = $this->app->request->getQueryParams()['params'] ?? [];
        $upload->files = $this->app->request->getUploadedFiles();
        $upload->urls = $this->app->request->getParsedBody()['urls'] ?? null;

        return $this->app->response->withJson($upload->getFiles());
    }
}
