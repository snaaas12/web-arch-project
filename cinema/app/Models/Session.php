<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'hall_id',
        'start_time',
        'end_time',
        'base_price',
        'format',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'base_price' => 'decimal:2',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Автоматически вычисляем end_time при создании
    protected static function booted()
    {
        static::creating(function ($session) {
            if (!$session->end_time && $session->movie) {
                $session->end_time = $session->start_time->copy()->addMinutes($session->movie->duration);
            }
        });
    }
}