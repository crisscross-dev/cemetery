<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CemeteryMapCalibration extends Model
{
    protected $fillable = [
        'top_left_lat',
        'top_left_lng',
        'top_left_svg_x',
        'top_left_svg_y',
        'bottom_right_lat',
        'bottom_right_lng',
        'bottom_right_svg_x',
        'bottom_right_svg_y',
    ];

    protected $casts = [
        'top_left_lat' => 'float',
        'top_left_lng' => 'float',
        'top_left_svg_x' => 'float',
        'top_left_svg_y' => 'float',
        'bottom_right_lat' => 'float',
        'bottom_right_lng' => 'float',
        'bottom_right_svg_x' => 'float',
        'bottom_right_svg_y' => 'float',
    ];
}
