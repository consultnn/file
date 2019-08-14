<?php

return [
    'uploadSecret' => ''<secret>'',
    'downloadSecret' => 0000000,
    'routes' => [
        'GET /{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}/{translit}.{extension:\w{3,4}}' => 'file',
        'GET /{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}.{extension:\w{3,4}}' => 'file',
        'GET /{file:\w+}_{hash:\w{1,7}}/{translit}.{extension:\w{3,4}}' => 'file',
        'GET /{file:\w+}_{hash:\w{1,7}}.{extension:\w{3,4}}' => 'file',
        'GET /upload/checkStatus/{token}' => 'status',
        'POST /uploadPdf/{uploadSecret:\w+}/{project:\w+}' => 'uploadPdf',
        'POST /upload/{uploadSecret:\w+}/{project:\w+}' => 'upload'
    ],
    'projects' => [
        'drivenn' => [],
        'domostroyrf' => [],
        'domostroydon' => [],
        'domostroynn' => [],
        'gipernn' => [],
        'banknn' => [],
        'vgoroden' => [],
    ],
    'handler' => [
        'file' => [],
        'upload' => [],
        'uploadPdf' => [],
        'status' => []
    ]
];
