<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_type',
        'device_token',
        'browser_type',
        'platform',
        'subscription_data',
        'is_active',
        'last_used_at'
    ];

    protected $casts = [
        'subscription_data' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for device type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
    }
}