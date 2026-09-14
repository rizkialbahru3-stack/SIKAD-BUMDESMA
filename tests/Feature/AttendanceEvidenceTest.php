<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(string $email = 'kry-901@bumdesma.test'): User
    {
        $user = User::create([
            'name' => 'Karyawan Tes',
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

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 640, 480);
    }

    public function test_check_in_requires_photo(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'latitude' => -6.9,
            'longitude' => 109.1,
            'accuracy' => 10,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('photo');
    }

    public function test_check_in_stores_photo_and_gps_with_server_time(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        Storage::fake('public');
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'photo' => $this->photo(),
            'latitude' => -6.9,
            'longitude' => 109.1,
            'accuracy' => 10,
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'type' => 'masuk']);
        $attendance = Attendance::first();
        $this->assertNotNull($attendance->check_in_at);
        $this->assertEquals('2026-09-10 08:00:00', $attendance->check_in_at->format('Y-m-d H:i:s'));
        $this->assertEquals('present', $attendance->status);
        Storage::disk('public')->assertExists($attendance->check_in_photo);
        $this->assertStringStartsWith('attendance/check-in/2026/09/', $attendance->check_in_photo);
        $this->assertEquals(-6.9, (float) $attendance->check_in_latitude);
        $this->assertEquals(109.1, (float) $attendance->check_in_longitude);
        $this->assertEquals(10, $attendance->check_in_accuracy);
    }

    public function test_duplicate_check_in_rejected(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $user = $this->employeeUser();

        $payload = ['photo' => $this->photo(), 'latitude' => -6.9, 'longitude' => 109.1, 'accuracy' => 10];
        $this->actingAs($user)->postJson('/attendance/check-in', $payload)->assertOk();
        $this->actingAs($user)->postJson('/attendance/check-in', $payload)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Absensi masuk hari ini sudah tercatat.']);
    }

    public function test_check_in_outside_enforced_radius_rejected(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        AttendanceLocation::create([
            'name' => 'Kantor', 'latitude' => -6.9, 'longitude' => 109.1,
            'radius_meters' => 100, 'max_accuracy_meters' => 100,
            'enforce_radius' => true, 'is_active' => true,
        ]);
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'photo' => $this->photo(),
            'latitude' => -6.0, // ~100 km dari kantor
            'longitude' => 109.1,
            'accuracy' => 10,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('luar area absensi', $response->json('message'));
        $this->assertNull(Attendance::first()?->check_in_at);
    }

    public function test_check_in_poor_accuracy_rejected(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        AttendanceLocation::create([
            'name' => 'Kantor', 'latitude' => -6.9, 'longitude' => 109.1,
            'radius_meters' => 100, 'max_accuracy_meters' => 100,
            'enforce_radius' => true, 'is_active' => true,
        ]);
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->postJson('/attendance/check-in', [
            'photo' => $this->photo(),
            'latitude' => -6.9,
            'longitude' => 109.1,
            'accuracy' => 500,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('belum cukup akurat', $response->json('message'));
    }

    public function test_check_out_requires_check_in_and_evidence_then_succeeds(): void
    {
        Storage::fake('public');
        $user = $this->employeeUser();

        Carbon::setTestNow('2026-09-10 15:05:00');
        $this->actingAs($user)->postJson('/attendance/check-out')
            ->assertStatus(422);

        Carbon::setTestNow('2026-09-10 08:02:00');
        $this->actingAs($user)->postJson('/attendance/check-in', [
            'photo' => $this->photo(), 'latitude' => -6.9, 'longitude' => 109.1, 'accuracy' => 10,
        ])->assertOk();

        Carbon::setTestNow('2026-09-10 15:05:00');
        $response = $this->actingAs($user)->postJson('/attendance/check-out', [
            'photo' => $this->photo(), 'latitude' => -6.9, 'longitude' => 109.1, 'accuracy' => 12,
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'type' => 'pulang']);
        $attendance = Attendance::first();
        $this->assertEquals('2026-09-10 15:05:00', $attendance->check_out_at->format('Y-m-d H:i:s'));
        Storage::disk('public')->assertExists($attendance->check_out_photo);
        $this->assertStringStartsWith('attendance/check-out/2026/09/', $attendance->check_out_photo);

        $this->actingAs($user)->postJson('/attendance/check-out', [
            'photo' => $this->photo(), 'latitude' => -6.9, 'longitude' => 109.1, 'accuracy' => 12,
        ])->assertStatus(422);
    }

    public function test_employee_cannot_view_other_attendance_but_admin_can(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $owner = $this->employeeUser('kry-902@bumdesma.test');
        $other = $this->employeeUser('kry-903@bumdesma.test');
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-tes@bumdesma.test', 'role' => 'admin', 'password' => Hash::make('password')]);

        $this->actingAs($owner)->postJson('/attendance/check-in', [
            'photo' => $this->photo(), 'latitude' => -6.9, 'longitude' => 109.1, 'accuracy' => 10,
        ])->assertOk();
        $attendance = Attendance::first();

        $this->actingAs($other)->get(route('attendance.show', $attendance))->assertForbidden();
        $this->actingAs($owner)->get(route('attendance.show', $attendance))->assertOk();
        $this->actingAs($admin)->get(route('attendance.show', $attendance))->assertOk();
    }

    public function test_admin_cannot_check_in(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        Storage::fake('public');
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-tes2@bumdesma.test', 'role' => 'admin', 'password' => Hash::make('password')]);

        $this->actingAs($admin)->postJson('/attendance/check-in', [
            'photo' => $this->photo(), 'latitude' => -6.9, 'longitude' => 109.1, 'accuracy' => 10,
        ])->assertForbidden();
    }

    public function test_checkout_from_field_allowed_while_checkin_enforced(): void
    {
        Storage::fake('public');
        AttendanceLocation::create([
            'name' => 'Kantor', 'latitude' => -6.926433, 'longitude' => 109.185844,
            'radius_meters' => 100, 'max_accuracy_meters' => 500,
            'enforce_radius' => true, 'enforce_checkout_radius' => false, 'is_active' => true,
        ]);
        $user = $this->employeeUser();

        Carbon::setTestNow('2026-09-10 08:00:00');
        $this->actingAs($user)->postJson('/attendance/check-in', [
            'photo' => $this->photo(), 'latitude' => -6.926433, 'longitude' => 109.185844, 'accuracy' => 10,
        ])->assertOk();

        // Pulang dari lapangan (±100 km) tetap bisa, lokasi tercatat tanpa penegakan.
        Carbon::setTestNow('2026-09-10 15:05:00');
        $response = $this->actingAs($user)->postJson('/attendance/check-out', [
            'photo' => $this->photo(), 'latitude' => -6.0, 'longitude' => 109.1, 'accuracy' => 20,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $attendance = Attendance::first();
        $this->assertNotNull($attendance->check_out_at);
        $this->assertNull($attendance->check_out_location_valid);
        $this->assertNotNull($attendance->check_out_latitude);
        $this->assertGreaterThan(100, $attendance->check_out_distance_meters);
    }

    public function test_employee_dashboard_renders_capture_modals(): void
    {
        Carbon::setTestNow('2026-09-10 08:02:00');
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('checkInModal', false);
        $response->assertSee('checkOutModal', false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
