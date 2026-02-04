<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'band_level',
        'duration_minutes',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(MockTestSection::class);
    }

    public function attempts()
    {
        return $this->hasMany(MockTestAttempt::class);
    }

    /**
     * Scope by band level
     */
    public function scopeByBandLevel($query, $bandLevel)
    {
        return $query->where('band_level', $bandLevel);
    }

    /**
     * Scope for user accessible mock tests
     */
    public function scopeAccessibleByUser($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('band_level', $user->getBandLevel())
                    ->where('is_active', true);
    }

    /**
     * Scope for active mock tests
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for available mock tests (within date range)
     */
    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('available_from')
              ->orWhere('available_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('available_until')
              ->orWhere('available_until', '>=', $now);
        });
    }
}
