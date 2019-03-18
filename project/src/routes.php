<?php

$app->get('/upload/checkStatus/{token}', new \middlewares\StatusMiddleware);
$app->post('/upload/{uploadSecret:\w+}/{project:\w+}', new \middlewares\UploadMiddleware($container['settings']));

$app->get('/{file:\w+}_{hash:\w{1,7}}.{extension:\w{3,4}}', new \middlewares\FileMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}/{translit}.{extension:\w{3,4}}', new \middlewares\FileMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}.{extension:\w{3,4}}', new \middlewares\FileMiddleware($container['settings']));
$app->get('/{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}/{translit}.{extension:\w{3,4}}', new \middlewares\FileMiddleware($container['settings']));
