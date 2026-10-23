<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id', 'cancha_id', 'starts_at', 'ends_at', 'total', 'status',
        'invoice_number', 'payment_method', 'payment_status',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'total' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }
}
