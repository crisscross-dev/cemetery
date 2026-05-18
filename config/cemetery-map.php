<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GPS to SVG Calibration
    |--------------------------------------------------------------------------
    |
    | These two real-world anchor points map GPS latitude/longitude onto the
    | Figma/SVG coordinate system used by the cemetery layout. Leave values as
    | null until the physical points have been measured in the cemetery.
    |
    */
    'calibration' => [
        'top_left' => [
            'lat' => env('CEMETERY_TOP_LEFT_LAT'),
            'lng' => env('CEMETERY_TOP_LEFT_LNG'),
            'x' => env('CEMETERY_TOP_LEFT_SVG_X', 0),
            'y' => env('CEMETERY_TOP_LEFT_SVG_Y', 0),
        ],
        'bottom_right' => [
            'lat' => env('CEMETERY_BOTTOM_RIGHT_LAT'),
            'lng' => env('CEMETERY_BOTTOM_RIGHT_LNG'),
            'x' => env('CEMETERY_BOTTOM_RIGHT_SVG_X', 1498),
            'y' => env('CEMETERY_BOTTOM_RIGHT_SVG_Y', 1190),
        ],
    ],
];
