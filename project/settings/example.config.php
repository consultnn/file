<?php

return [
    'app' => [
        'components' => [
            'filesystem' => [
                'class' => \components\Filesystem::class
            ]
        ],
        'routes' => [
            'GET /{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}/{translit}.{extension:\w{3,4}}' => 'image',
            'GET /{file:\w+}_{hash:\w{1,7}}{params:_[\w\_\|\.\*-]+}.{extension:\w{3,4}}' => 'image',
            'GET /{file:\w+}_{hash:\w{1,7}}/{translit}.{extension:\w{3,4}}' => 'image',
            'GET /{file:\w+}_{hash:\w{1,7}}.{extension:\w{3,4}}' => 'image',
            'POST /upload/{uploadSecret:\w+}/{project:\w+}' => 'upload'
        ],
        'handler' => [
            'image' => [
                'class' => \handlers\Image::class,
                'downloadSecret' => '000000'
            ],
            'upload' => [
                'class' => \handlers\Upload::class,
                'uploadSecret' => 'secret'
            ]
        ]
    ],
    'projects' => [
        'gipernn' => [
            'handler' => [
                'image' => [
                    'watermark' => [
                        'text' => 'GIPERNN.RU',
                        'fontSizeCoefficient' => 80,
                        'hexColor' => 'ffffff',
                        'font' => 'generis',
                        'opacity' => 25,
                        'angle' => -35,
                        'minSize' => 150,
                        'marginCoefficient' => 10
                    ]
                ]
            ]
        ],
    ],
];
