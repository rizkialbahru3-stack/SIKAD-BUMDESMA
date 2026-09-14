<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = ['employee_id', 'type', 'start_date', 'end_date', 'total_days', 'reason', 'attachment_path', 'status', 'reviewed_by', 'reviewed_at', 'review_note', 'rejection_reason'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'reviewed_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
