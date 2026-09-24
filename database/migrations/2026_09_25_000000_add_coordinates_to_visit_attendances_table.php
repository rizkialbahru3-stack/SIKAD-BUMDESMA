<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_attendances', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('photo');
            $table->decimal('longitude', 11, 7)->nullable()->after('latitude');
            $table->unsignedInteger('accuracy')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('visit_attendances', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'accuracy']);
        });
    }
};
