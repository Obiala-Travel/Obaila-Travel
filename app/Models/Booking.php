<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'reference',
        'provider_booking_id',
        'origin',
        'destination',
        'depart_at',
        'return_at',
        'passengers',
        'cabin_class',
        'total_price',
        'currency',
        'status',
        'raw_response',
    ];

    protected $casts = [
        'depart_at'    => 'datetime',
        'return_at'    => 'datetime',
        'raw_response' => 'array',
        'total_price'  => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
