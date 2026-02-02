<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    protected $table = 'blocked_dates';

    protected $fillable = [
        'data',
        'motivo',
        'created_by',
    ];

    protected $casts = [
        'data' => 'date:Y-m-d',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
