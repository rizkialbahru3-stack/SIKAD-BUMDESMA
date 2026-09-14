<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoKaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::firstOrCreate(['name' => 'Staf Operasional']);
        $schedule = WorkSchedule::firstOrCreate(['name' => 'Jam Kerja Reguler'], [
            'start_time' => '08:00',
            'end_time' => '15:00',
            'late_tolerance_minutes' => 15,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);

        $karyawan = [
            ['code' => 'KRY-002', 'name' => 'Karyawan 2', 'email' => 'kry-002@bumdesma.test', 'phone' => '081234567891'],
            ['code' => 'KRY-003', 'name' => 'Karyawan 3', 'email' => 'kry-003@bumdesma.test', 'phone' => '081234567892'],
            ['code' => 'KRY-004', 'name' => 'Karyawan 4', 'email' => 'kry-004@bumdesma.test', 'phone' => '081234567893'],
            ['code' => 'KRY-005', 'name' => 'Karyawan 5', 'email' => 'kry-005@bumdesma.test', 'phone' => '081234567894'],
            ['code' => 'KRY-006', 'name' => 'Karyawan 6', 'email' => 'kry-006@bumdesma.test', 'phone' => '081234567895'],
        ];

        foreach ($karyawan as $item) {
            $user = User::updateOrCreate(['email' => $item['email']], [
                'name' => $item['name'],
                'role' => 'employee',
                'password' => Hash::make('password'),
            ]);

            Employee::updateOrCreate(['employee_code' => $item['code']], [
                'user_id' => $user->id,
                'position_id' => $position->id,
                'work_schedule_id' => $schedule->id,
                'phone' => $item['phone'],
                'joined_at' => now()->startOfYear(),
                'basic_salary' => 3000000,
                'attendance_allowance' => 500000,
                'is_active' => true,
            ]);
        }
    }
}
