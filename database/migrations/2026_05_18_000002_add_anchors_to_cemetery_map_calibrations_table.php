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
        Schema::table('cemetery_map_calibrations', function (Blueprint $table) {
            $table->json('anchors')->nullable()->after('bottom_right_svg_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cemetery_map_calibrations', function (Blueprint $table) {
            $table->dropColumn('anchors');
        });
    }
};
