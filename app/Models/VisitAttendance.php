<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitAttendance extends Model
{
    public const MAX_PER_MONTH = 10;

    protected $fillable = ['employee_id', 'title', 'photo', 'visit_date'];

    protected $casts = ['visit_date' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
