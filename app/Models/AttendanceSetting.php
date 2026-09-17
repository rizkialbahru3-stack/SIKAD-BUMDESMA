<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'work_start_time',
        'work_end_time',
        'late_tolerance_minutes',
        'checkout_start_time',
        'checkout_end_time',
        'require_checkout_approval',
        'auto_late_points_enabled',
        'late_points_block_minutes',
        'late_points_per_block',
    ];

    protected $casts = ['require_checkout_approval' => 'boolean', 'auto_late_points_enabled' => 'boolean'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'work_start_time' => '08:00',
            'work_end_time' => '15:00',
            'late_tolerance_minutes' => 30,
            'checkout_start_time' => '14:30',
            'checkout_end_time' => '15:30',
            'require_checkout_approval' => true,
            'auto_late_points_enabled' => true,
            'late_points_block_minutes' => 15,
            'late_points_per_block' => 1,
        ]);
    }
}
