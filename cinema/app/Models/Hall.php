<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'rows_count',
        'seats_per_row',
        'seats_schema',
    ];

    protected $casts = [
        'seats_schema' => 'array',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}