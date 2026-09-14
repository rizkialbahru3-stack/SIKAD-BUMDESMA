<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    protected $fillable = ['employee_id', 'reward_rule_id', 'type', 'amount', 'points', 'title', 'description', 'awarded_at'];

    protected $casts = ['amount' => 'decimal:2', 'awarded_at' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
