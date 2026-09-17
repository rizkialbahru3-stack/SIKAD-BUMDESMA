<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('punishments', function (Blueprint $table) {
            $table->unsignedInteger('points')->default(0)->after('amount');
            $table->boolean('is_auto')->default(false)->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('punishments', function (Blueprint $table) {
            $table->dropColumn(['points', 'is_auto']);
        });
    }
};
