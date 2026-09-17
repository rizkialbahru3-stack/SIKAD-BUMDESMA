<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->boolean('auto_late_points_enabled')->default(true)->after('require_checkout_approval');
            $table->unsignedSmallInteger('late_points_block_minutes')->default(15)->after('auto_late_points_enabled');
            $table->unsignedSmallInteger('late_points_per_block')->default(1)->after('late_points_block_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn(['auto_late_points_enabled', 'late_points_block_minutes', 'late_points_per_block']);
        });
    }
};
