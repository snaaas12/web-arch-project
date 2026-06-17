<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'seats',
        'status',
        'total_price',
        'qr_code',
        'locked_until',
    ];

    protected $casts = [
        'seats' => 'array',
        'total_price' => 'decimal:2',
        'locked_until' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    // Проверка, истекла ли блокировка
    public function isExpired(): bool
    {
        return $this->locked_until && $this->locked_until->isPast();
    }
}