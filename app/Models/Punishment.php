<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Punishment extends Model
{
    protected $fillable = ['employee_id', 'punishment_rule_id', 'type', 'amount', 'title', 'description', 'issued_at'];

    protected $casts = ['amount' => 'decimal:2', 'issued_at' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
