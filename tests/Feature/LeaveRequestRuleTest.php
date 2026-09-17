<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveRequestRuleTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $user = User::create([
            'name' => 'Karyawan Cuti',
            'email' => 'kry-cuti@bumdesma.test',
            'role' => 'employee',
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'KRY-CUTI',
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_leave_requires_seven_days_notice(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        // H+3 ditolak.
        $this->actingAs($user)->post(route('leave.store'), [
            'type' => 'leave',
            'start_date' => '2026-09-19',
            'end_date' => '2026-09-20',
            'reason' => 'Liburan.',
        ])->assertSessionHasErrors('start_date');
        $this->assertEquals(0, LeaveRequest::count());

        // Tepat H+7 diterima.
        $this->actingAs($user)->post(route('leave.store'), [
            'type' => 'leave',
            'start_date' => '2026-09-23',
            'end_date' => '2026-09-24',
            'reason' => 'Liburan.',
        ])->assertRedirect();
        $this->assertEquals(1, LeaveRequest::count());
    }

    public function test_permission_type_is_rejected(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'type' => 'permission',
            'start_date' => '2026-09-23',
            'end_date' => '2026-09-23',
            'reason' => 'Urusan keluarga.',
        ])->assertSessionHasErrors('type');
        $this->assertEquals(0, LeaveRequest::count());
    }

    public function test_sick_is_exempt_from_notice_rule(): void
    {
        Carbon::setTestNow('2026-09-16 10:00:00');
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('leave.store'), [
            'type' => 'sick',
            'start_date' => '2026-09-17',
            'end_date' => '2026-09-17',
            'reason' => 'Demam.',
        ])->assertRedirect();
        $this->assertEquals(1, LeaveRequest::count());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
