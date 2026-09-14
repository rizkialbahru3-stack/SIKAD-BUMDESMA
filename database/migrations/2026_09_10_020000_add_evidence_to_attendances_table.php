<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_photo')->nullable()->after('check_in_at');
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('check_in_photo');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
            $table->unsignedInteger('check_in_accuracy')->nullable()->after('check_in_longitude');
            $table->unsignedInteger('check_in_distance_meters')->nullable()->after('check_in_accuracy');
            $table->boolean('check_in_location_valid')->nullable()->after('check_in_distance_meters');

            $table->string('check_out_photo')->nullable()->after('check_out_at');
            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_out_photo');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
            $table->unsignedInteger('check_out_accuracy')->nullable()->after('check_out_longitude');
            $table->unsignedInteger('check_out_distance_meters')->nullable()->after('check_out_accuracy');
            $table->boolean('check_out_location_valid')->nullable()->after('check_out_distance_meters');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_photo', 'check_in_latitude', 'check_in_longitude',
                'check_in_accuracy', 'check_in_distance_meters', 'check_in_location_valid',
                'check_out_photo', 'check_out_latitude', 'check_out_longitude',
                'check_out_accuracy', 'check_out_distance_meters', 'check_out_location_valid',
            ]);
        });
    }
};
