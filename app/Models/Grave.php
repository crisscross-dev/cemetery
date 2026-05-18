<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grave extends Model
{
    protected $fillable = [
        'grave_number',
        'x_position',
        'y_position',
        'width',
        'height',
        'rotation',
        'status',
        'deceased_name',
        'date_of_birth',
        'date_of_death',
        'image_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_death' => 'date',
    ];
}
