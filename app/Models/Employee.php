<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'position_id', 'work_schedule_id', 'employee_code', 'name', 'phone', 'email', 'photo', 'photo_updated_at', 'joined_at', 'basic_salary', 'attendance_allowance', 'is_active'];

    protected $casts = ['joined_at' => 'date', 'photo_updated_at' => 'datetime', 'basic_salary' => 'decimal:2', 'attendance_allowance' => 'decimal:2', 'is_active' => 'boolean'];

    protected $appends = ['display_name', 'display_email'];

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: ($this->user?->name ?? '-');
    }

    public function getDisplayEmailAttribute(): ?string
    {
        return $this->email ?: $this->user?->email;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(Punishment::class);
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
