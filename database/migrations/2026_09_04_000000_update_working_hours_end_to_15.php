<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_schedules')->where('end_time', '16:00:00')->update(['end_time' => '15:00:00']);
    }

    public function down(): void
    {
        DB::table('work_schedules')->where('end_time', '15:00:00')->update(['end_time' => '16:00:00']);
    }
};
