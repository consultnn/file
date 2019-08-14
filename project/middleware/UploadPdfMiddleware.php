<?php

namespace middleware;

use Application;
use components\UploadPdf;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Response;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class UploadPdfMiddleware
 * @package middlewares
 */
class UploadPdfMiddleware implements RequestHandlerInterface
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

        $upload = new UploadPdf();
        $upload->project = $request->getAttribute('project');
        $upload->params = $request->getQueryParams()['params'] ?? [];
        $upload->files = $request->getUploadedFiles();
        $upload->token = isset($request->getQueryParams()['token']) ? $request->getQueryParams()['token'] : null;

        return new JsonResponse($upload->getFiles());
    }
}