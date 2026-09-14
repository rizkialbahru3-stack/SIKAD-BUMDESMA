<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ContentSeeder::class);
        $this->call(AttendanceSeeder::class);
        // Data karyawan demo (KRY-002–KRY-006) hanya untuk non-production.
        if (! app()->isProduction()) {
            $this->call(DemoKaryawanSeeder::class);
        }
        $this->call(AttendanceLocationSeeder::class);
    }
}
