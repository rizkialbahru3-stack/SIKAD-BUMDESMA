<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitAttendance extends Model
{
    public const MAX_PER_DAY = 10;

    protected $fillable = ['employee_id', 'title', 'description', 'photo', 'visit_date', 'latitude', 'longitude', 'accuracy'];

    protected $casts = ['visit_date' => 'date', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
