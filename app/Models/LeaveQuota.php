<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveQuota extends Model
{
    protected $fillable = ['year', 'quota_days'];

    public static function current(): self
    {
        return static::firstOrCreate(['year' => (int) now()->format('Y')], ['quota_days' => 12]);
    }

    /**
     * Quota usage for approved/pending CUTI (type=leave) only.
     * Pending requests never reduce the remaining quota permanently.
     */
    public static function usage(Employee $employee, ?int $year = null): array
    {
        $year ??= (int) now()->format('Y');
        $entitlement = static::firstOrCreate(['year' => $year], ['quota_days' => 12])->quota_days;

        $days = fn (string $status) => (int) LeaveRequest::where('employee_id', $employee->id)
            ->where('type', 'leave')
            ->where('status', $status)
            ->whereYear('start_date', $year)
            ->sum('total_days');

        $used = $days('approved');
        $pending = $days('pending');

        return [
            'entitlement' => $entitlement,
            'used' => $used,
            'pending' => $pending,
            'remaining' => max(0, $entitlement - $used),
        ];
    }
}
