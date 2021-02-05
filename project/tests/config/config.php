<?php
return [
    'app' => [
        'handler' => [
            'image' => [
                'downloadSecret' => 2534534
            ],
        ]
    ],
    'projects' => [
        'example' => [
            'handler' => [
                'upload' => [
                    'class' => \handlers\Upload::class,
                    'uploadSecret' => 'N3edBMSnQrakH9nBK98Gmmrz367JxWCT'
                ]
            ]
        ],
    ],
];
