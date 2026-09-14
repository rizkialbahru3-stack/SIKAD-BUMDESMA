<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id', 'period_start', 'period_end', 'basic_salary', 'attendance_allowance',
        'reward_total', 'late_deduction', 'absence_deduction', 'punishment_total', 'net_salary', 'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'basic_salary' => 'decimal:2',
        'attendance_allowance' => 'decimal:2',
        'reward_total' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'punishment_total' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
