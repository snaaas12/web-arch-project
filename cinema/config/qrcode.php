<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Backend
    |--------------------------------------------------------------------------
    |
    | Specify the image backend to use when generating QR codes.
    | Available options: 'png' (GD) or 'svg'
    | 'png' uses GD extension (already installed in our Docker)
    |
    */
    'image_backend' => 'png',

    /*
    |--------------------------------------------------------------------------
    | Default Generator
    |--------------------------------------------------------------------------
    */
    'generator' => [
        'encoding' => 'UTF-8',
        'size' => 300,
        'margin' => 0,
    ],
];