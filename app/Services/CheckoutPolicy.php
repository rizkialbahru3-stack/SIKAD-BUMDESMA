<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use Carbon\Carbon;

class CheckoutPolicy
{
    public const STATUS_LABELS = [
        'waiting' => 'Menunggu',
        'approved' => 'Diizinkan',
        'rejected' => 'Ditolak',
        'checked_out' => 'Sudah Pulang',
    ];

    public const TYPE_LABELS = [
        'normal' => 'Pulang Normal',
        'early' => 'Pulang Lebih Awal',
        'special' => 'Izin Khusus',
    ];

    public static function settings(): AttendanceSetting
    {
        return AttendanceSetting::current();
    }

    /**
     * Resolve work times with priority: employee schedule > system settings.
     * Checkout window follows the employee's end time using the same offsets
     * as the settings define around the default end time.
     */
    public static function workTimes(Employee $employee, ?Carbon $date = null): array
    {
        $settings = static::settings();
        $schedule = $employee->workSchedule;
        $day = ($date ?? now())->toDateString();

        $start = $schedule?->start_time ?? substr((string) $settings->work_start_time, 0, 5);
        $end = $schedule?->end_time ?? substr((string) $settings->work_end_time, 0, 5);
        $tolerance = $schedule?->late_tolerance_minutes ?? $settings->late_tolerance_minutes;

        $settingsEnd = Carbon::parse($day.' '.substr((string) $settings->work_end_time, 0, 5));
        $before = $settingsEnd->diffInMinutes(Carbon::parse($day.' '.substr((string) $settings->checkout_start_time, 0, 5)));
        $after = Carbon::parse($day.' '.substr((string) $settings->checkout_end_time, 0, 5))->diffInMinutes($settingsEnd);

        $endDt = Carbon::parse($day.' '.$end);

        return [
            'start' => Carbon::parse($day.' '.$start),
            'end' => $endDt,
            'tolerance' => (int) $tolerance,
            'window_start' => $endDt->copy()->subMinutes($before),
            'window_end' => $endDt->copy()->addMinutes($after),
        ];
    }

    /**
     * Server-side eligibility for check-out. Never trust browser time.
     *
     * Pulang normal langsung tanpa persetujuan admin selama masih dalam
     * rentang waktu pulang. Persetujuan admin hanya dibutuhkan untuk
     * pulang lebih awal (sebelum rentang waktu dimulai).
     */
    public static function checkoutEligibility(Employee $employee, ?Attendance $attendance, ?Carbon $now = null): array
    {
        $now ??= now();

        if (! $attendance?->check_in_at) {
            return ['state' => 'no_checkin', 'allowed' => false, 'reason' => 'Lakukan absensi masuk terlebih dahulu.'];
        }

        if ($attendance->check_out_at) {
            return ['state' => 'done', 'allowed' => false, 'reason' => 'Absensi pulang hari ini sudah tercatat.'];
        }

        $times = static::workTimes($employee, $now);
        $earlyBypass = $attendance->checkout_status === 'approved'
            && in_array($attendance->checkout_approval_type, ['early', 'special'], true);

        if ($now->lt($times['window_start']) && ! $earlyBypass) {
            if ($attendance->checkout_status === 'rejected') {
                return ['state' => 'rejected', 'allowed' => false, 'reason' => 'Pengajuan pulang awal ditolak Admin. Hubungi Admin untuk informasi lebih lanjut.'];
            }

            return ['state' => 'too_early', 'allowed' => false, 'reason' => 'Absensi pulang tersedia mulai pukul '.$times['window_start']->format('H:i').'. Untuk pulang lebih awal, minta persetujuan Admin terlebih dahulu.'];
        }

        if ($now->gt($times['window_end']) && ! $earlyBypass) {
            return ['state' => 'too_late', 'allowed' => false, 'reason' => 'Rentang absensi pulang telah berakhir (sampai pukul '.$times['window_end']->format('H:i').').'];
        }

        return ['state' => 'allowed', 'allowed' => true, 'reason' => 'Anda sudah dapat melakukan absensi pulang.'];
    }
}
