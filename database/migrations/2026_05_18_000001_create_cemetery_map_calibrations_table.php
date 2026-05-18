<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cemetery_map_calibrations', function (Blueprint $table) {
            $table->id();
            $table->decimal('top_left_lat', 11, 8);
            $table->decimal('top_left_lng', 11, 8);
            $table->decimal('top_left_svg_x', 10, 2);
            $table->decimal('top_left_svg_y', 10, 2);
            $table->decimal('bottom_right_lat', 11, 8);
            $table->decimal('bottom_right_lng', 11, 8);
            $table->decimal('bottom_right_svg_x', 10, 2);
            $table->decimal('bottom_right_svg_y', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cemetery_map_calibrations');
    }
};
