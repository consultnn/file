<?php

$app->group('/upload', function () use ($app) {
    $app->get('/checkStatus/{token}', new \middleware\StatusMiddleware);
    $app->post('/{uploadSecret:\w+}/{project:\w+}', new \middleware\UploadMiddleware($app->getContainer()['settings']));
});
$app->post('/uploadPdf/{uploadSecret:\w+}/{project:\w+}', new \middleware\UploadPdfMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}.{extension:\w{3,4}}', new \middleware\FileMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}/{translit}.{extension:\w{3,4}}', new \middleware\FileMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}.{extension:\w{3,4}}', new \middleware\FileMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}/{translit}.{extension:\w{3,4}}', new \middleware\FileMiddleware($container['settings']));
