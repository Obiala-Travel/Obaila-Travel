<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFlight extends Model
{
    protected $fillable = [
        'user_id',
        'origin',
        'destination',
        'depart_at',
        'price',
        'currency',
        'flight_data',
    ];

    protected $casts = [
        'depart_at'   => 'datetime',
        'flight_data' => 'array',
        'price'       => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
