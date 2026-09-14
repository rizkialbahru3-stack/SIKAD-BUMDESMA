<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'late_tolerance_minutes', 'working_days', 'is_active'];

    protected $casts = ['working_days' => 'array', 'is_active' => 'boolean'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
