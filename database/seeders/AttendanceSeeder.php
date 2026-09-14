<?php

namespace Database\Seeders;

use App\Models\Employee;
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
        $employeeUser = User::updateOrCreate(['email' => 'kry-001@bumdesma.test'], [
            'name' => 'Karyawan 1',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);

        Employee::updateOrCreate(['employee_code' => 'KRY-001'], [
            'user_id' => $employeeUser->id,
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'phone' => '081234567890',
            'joined_at' => now()->startOfYear(),
            'basic_salary' => 3000000,
            'attendance_allowance' => 500000,
            'is_active' => true,
        ]);

        $admin->update(['role' => 'admin']);
    }
}
