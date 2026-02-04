<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module_type',
        'description',
        'band_level',
        'ai_generation_config',
        'supports_ai_generation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_ai_generation' => 'boolean',
        'ai_generation_config' => 'array',
    ];

    /**
     * Scope for active modules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for modules that support AI generation
     */
    public function scopeSupportsAI($query)
    {
        return $query->where('supports_ai_generation', true);
    }

    /**
     * Scope by module type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('module_type', $type);
    }

    /**
     * Scope by band level
     */
    public function scopeByBandLevel($query, $bandLevel)
    {
        return $query->where('band_level', $bandLevel);
    }

    /**
     * Scope for user accessible modules
     */
    public function scopeAccessibleByUser($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('band_level', $user->getBandLevel());
    }

    /**
     * Get questions for this module
     */
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'module_questions')
                    ->withPivot('order')
                    ->withTimestamps()
                    ->orderBy('pivot_order');
    }
}