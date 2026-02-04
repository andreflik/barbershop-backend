<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedPeriod extends Model
{
    protected $table = 'blocked_periods';

    protected $fillable = [
        'date',
        'time',
        'is_full_day',
        'is_recurring',
        'weekday',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_full_day'  => 'boolean',
        'is_recurring' => 'boolean',
        'weekday'      => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDaily(): bool
    {
        return $this->is_recurring && is_null($this->weekday);
    }

    public function isWeekly(): bool
    {
        return $this->is_recurring && !is_null($this->weekday);
    }
}
