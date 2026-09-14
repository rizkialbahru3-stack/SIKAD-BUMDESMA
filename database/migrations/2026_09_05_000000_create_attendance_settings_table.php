<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->time('work_start_time')->default('08:00');
            $table->time('work_end_time')->default('15:00');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(30);
            $table->time('checkout_start_time')->default('14:30');
            $table->time('checkout_end_time')->default('15:30');
            $table->boolean('require_checkout_approval')->default(true);
            $table->timestamps();
        });

        DB::table('attendance_settings')->insert([
            'id' => 1,
            'work_start_time' => '08:00',
            'work_end_time' => '15:00',
            'late_tolerance_minutes' => 30,
            'checkout_start_time' => '14:30',
            'checkout_end_time' => '15:30',
            'require_checkout_approval' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
