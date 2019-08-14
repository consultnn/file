<?php

namespace middleware;

use Application;
use components\Upload;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Response;
use Zend\Diactoros\Response\JsonResponse;

class UploadMiddleware implements RequestHandlerInterface
{
    /**
     * Handles a request and produces a response.
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response;
        if ($request->getAttribute('uploadSecret') !== Application::getConfigValue('uploadSecret')) {
            return $response->withStatus(400);
        }

        $upload = new Upload;
        $upload->project = $request->getAttribute('project');
        $upload->params = $request->getQueryParams()['params'] ?? [];
        $upload->files = $request->getUploadedFiles();
        $upload->urls = isset($request->getParsedBody()['urls']) ? $request->getParsedBody()['urls'] : null;

        return new JsonResponse($upload->getFiles());
    }
}