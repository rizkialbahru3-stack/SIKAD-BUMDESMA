<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceLocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $location = AttendanceLocation::updateOrCreate(
                ['name' => 'Kantor BUMDESMA TARUB LKD'],
                [
                    'address' => 'Kantor Bumdesma Tarub LKD, Tarub, Kabupaten Tegal 52184',
                    'latitude' => -6.926433,
                    'longitude' => 109.185844,
                    'radius_meters' => 100,
                    // Longgar agar GPS laptop (WiFi, akurasi ratusan meter) tetap bisa absen;
                    // admin bisa mengetatkan lagi via Kehadiran → Lokasi bila absen hanya via HP.
                    'max_accuracy_meters' => 500,
                    'enforce_radius' => true,
                    'is_active' => true,
                ]
            );

            AttendanceLocation::whereKeyNot($location->id)->update(['is_active' => false]);
        });
    }
}
