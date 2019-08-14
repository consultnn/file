<?php

return [
    'drivenn' => [],
    'domostroyrf' => [],
    'domostroydon' => [],
    'domostroynn' => [],
    'gipernn' => [],
    'banknn' => [
        'uploadSecret' => ''<secret>'',
        'downloadSecret' => 0000000,
    ],
    'vgoroden' => [],
    'default' => [
        'uploadSecret' => ''<secret>'',
        'downloadSecret' => 0000000,
        'routes' => [
            'GET /{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}/{translit}.{extension:\w{3,4}}' => \middleware\FileMiddleware::class,
            'GET /{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}.{extension:\w{3,4}}' => \middleware\FileMiddleware::class,
            'GET /{file:\w+}_{hash:\w{1,7}}/{translit}.{extension:\w{3,4}}' => \middleware\FileMiddleware::class,
            'GET /{file:\w+}_{hash:\w{1,7}}.{extension:\w{3,4}}' => \middleware\FileMiddleware::class,
            'GET /upload/checkStatus/{token}' => \middleware\StatusMiddleware::class,
            'POST /uploadPdf/{uploadSecret:\w+}/{project:\w+}' => \middleware\UploadPdfMiddleware::class,
            'POST /upload/{uploadSecret:\w+}/{project:\w+}' => \middleware\UploadMiddleware::class
        ]
    ]
];
