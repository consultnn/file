<?php

namespace middleware;

use components\UploadPdf;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Response;

/**
 * Class UploadPdfMiddleware
 * @package middlewares
 */
class UploadPdfMiddleware implements RequestHandlerInterface
{
    private $response;
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->response = new Response();
    }

    /**
     * Handles a request and produces a response.
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getAttribute('uploadSecret') !== $this->settings['uploadSecret']) {
            return $this->response->withStatus(400);
        }

        $upload = new UploadPdf();
        $upload->project = $request->getAttribute('project');
        $upload->params = $request->getQueryParams()['params'] ?? [];
        $upload->files = $request->getUploadedFiles();
        $upload->token = isset($request->getQueryParams()['token']) ? $request->getQueryParams()['token'] : null;

        return $this->response->withJson($upload->getFiles());
    }
}