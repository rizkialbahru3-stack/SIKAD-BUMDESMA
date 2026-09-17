<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::updateOrCreate(['name' => 'Staff'], ['description' => 'Staff BUMDESMA LKD TARUB']);
        $schedule = WorkSchedule::firstOrCreate(['name' => 'Jam Kerja Reguler'], [
            'start_time' => '08:00',
            'end_time' => '15:00',
            'late_tolerance_minutes' => 15,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);

        // Akun demo (password "password") hanya dibuat di luar production.
        // Di production, buat akun admin via `php artisan admin:create`.
        if (app()->isProduction()) {
            return;
        }

        $admin = User::updateOrCreate(['email' => 'admin@bumdesma.test'], [
            'name' => 'Administrator',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $admin->update(['role' => 'admin']);
    }
}
