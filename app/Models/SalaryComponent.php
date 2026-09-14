<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    protected $fillable = ['employee_id', 'name', 'type', 'amount', 'is_active'];

    protected $casts = ['amount' => 'decimal:2', 'is_active' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
