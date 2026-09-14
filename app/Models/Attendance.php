<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['employee_id', 'attendance_date', 'check_in_at', 'check_out_at', 'late_minutes', 'work_minutes', 'status', 'note', 'checkout_status', 'checkout_approval_type', 'checkout_approved_at', 'checkout_approved_by', 'check_in_photo', 'check_in_latitude', 'check_in_longitude', 'check_in_accuracy', 'check_in_distance_meters', 'check_in_location_valid', 'check_out_photo', 'check_out_latitude', 'check_out_longitude', 'check_out_accuracy', 'check_out_distance_meters', 'check_out_location_valid'];

    protected $casts = ['attendance_date' => 'date', 'check_in_at' => 'datetime', 'check_out_at' => 'datetime', 'checkout_approved_at' => 'datetime', 'check_in_location_valid' => 'boolean', 'check_out_location_valid' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checkout_approved_by');
    }
}
