<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(string $email = 'admin-koreksi@bumdesma.test'): User
    {
        return User::create([
            'name' => 'Admin Koreksi',
            'email' => $email,
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    private function employeeUser(string $email = 'kry-koreksi@bumdesma.test'): User
    {
        $user = User::create([
            'name' => 'Karyawan Koreksi',
            'email' => $email,
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'KRY-'.substr(str_replace(['@', '.', '-'], '', $email), 0, 10),
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_admin_can_input_manual_attendance(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;

        $response = $this->actingAs($admin)->post(route('attendance.corrections.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'check_in' => '08:05',
            'check_out' => '15:00',
            'status' => 'present',
            'note' => 'Lupa absen, HP mati.',
        ]);

        $response->assertRedirect(route('attendance.index'));
        $attendance = Attendance::first();
        $this->assertEquals('2026-09-16 08:05:00', $attendance->check_in_at->format('Y-m-d H:i:s'));
        $this->assertEquals(5, $attendance->late_minutes);
        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(415, $attendance->work_minutes);
        $this->assertEquals('checked_out', $attendance->checkout_status);
        $this->assertEquals('Lupa absen, HP mati.', $attendance->note);
        $this->assertNull($attendance->check_in_photo);
    }

    public function test_manual_attendance_duplicate_date_rejected(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'check_in_at' => '2026-09-16 08:00:00',
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)->post(route('attendance.corrections.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'check_in' => '08:00',
            'status' => 'present',
        ]);

        $response->assertSessionHasErrors('attendance_date');
        $this->assertEquals(1, Attendance::count());
    }

    public function test_manual_attendance_without_checkin_needs_leave_status(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;

        $response = $this->actingAs($admin)->post(route('attendance.corrections.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'status' => 'present',
        ]);

        $response->assertSessionHasErrors('check_in');

        $this->actingAs($admin)->post(route('attendance.corrections.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'status' => 'sick',
            'note' => 'Sakit, ada surat dokter.',
        ])->assertRedirect(route('attendance.index'));

        $attendance = Attendance::first();
        $this->assertEquals('sick', $attendance->status);
        $this->assertNull($attendance->check_in_at);
    }

    public function test_admin_can_update_correction_and_resets_approval(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'check_in_at' => '2026-09-16 08:00:00',
            'check_out_at' => '2026-09-16 14:00:00',
            'status' => 'present',
            'checkout_status' => 'approved',
            'checkout_approval_type' => 'early',
            'checkout_approved_by' => $admin->id,
            'checkout_approved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('attendance.corrections.update', $attendance), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'check_in' => '08:10',
            'check_out' => '15:00',
            'status' => 'present',
        ]);

        $response->assertRedirect(route('attendance.index'));
        $attendance->refresh();
        $this->assertEquals(10, $attendance->late_minutes);
        $this->assertEquals('late', $attendance->status);
        $this->assertEquals('checked_out', $attendance->checkout_status);
        $this->assertNull($attendance->checkout_approved_by);
        $this->assertNull($attendance->checkout_approval_type);
    }

    public function test_admin_can_view_correction_forms_and_index(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $admin = $this->adminUser();
        $employee = $this->employeeUser()->employee;
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-16',
            'check_in_at' => '2026-09-16 08:00:00',
            'status' => 'present',
            'note' => 'Koreksi percobaan.',
        ]);

        $this->actingAs($admin)->get(route('attendance.corrections.create'))
            ->assertOk()
            ->assertSee('Input Absensi Manual');
        $this->actingAs($admin)->get(route('attendance.corrections.edit', $attendance))
            ->assertOk()
            ->assertSee('Koreksi Absensi');
        $this->actingAs($admin)->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Input Manual')
            ->assertSee('Manual');
        $this->actingAs($admin)->get(route('attendance.show', $attendance))
            ->assertOk()
            ->assertSee('Koreksi percobaan.');
    }

    public function test_employee_cannot_access_corrections(): void
    {
        $user = $this->employeeUser();
        $attendance = Attendance::create([
            'employee_id' => $user->employee->id,
            'attendance_date' => '2026-09-16',
            'status' => 'present',
        ]);

        $this->actingAs($user)->get(route('attendance.corrections.create'))->assertForbidden();
        $this->actingAs($user)->post(route('attendance.corrections.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('attendance.corrections.edit', $attendance))->assertForbidden();
        $this->actingAs($user)->put(route('attendance.corrections.update', $attendance), [])->assertForbidden();
    }

    public function test_guest_cannot_access_corrections_or_holidays(): void
    {
        $this->get(route('attendance.corrections.create'))->assertRedirect(route('login'));
        $this->get(route('holidays.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_manage_holidays(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('holidays.store'), [
            'holiday_date' => '2026-12-25',
            'name' => 'Hari Raya Natal',
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('holidays.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Hari Raya Natal');

        $holiday = Holiday::first();
        $this->actingAs($admin)->put(route('holidays.update', $holiday), [
            'holiday_date' => '2026-12-25',
            'name' => 'Natal Bersama',
        ])->assertRedirect();
        $this->assertEquals('Natal Bersama', $holiday->fresh()->name);

        $this->actingAs($admin)->delete(route('holidays.destroy', $holiday))->assertRedirect();
        $this->assertEquals(0, Holiday::count());
    }

    public function test_holiday_duplicate_date_rejected(): void
    {
        $admin = $this->adminUser();
        Holiday::create(['holiday_date' => '2026-12-25', 'name' => 'Natal']);

        $this->actingAs($admin)->post(route('holidays.store'), [
            'holiday_date' => '2026-12-25',
            'name' => 'Libur lain',
        ])->assertSessionHasErrors('holiday_date');

        $this->assertEquals(1, Holiday::count());
    }

    public function test_employee_cannot_manage_holidays(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->get(route('holidays.index'))->assertForbidden();
        $this->actingAs($user)->post(route('holidays.store'), [])->assertForbidden();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
