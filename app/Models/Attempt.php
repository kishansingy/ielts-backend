<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_type',
        'content_id',
        'content_type',
        'score',
        'max_score',
        'time_spent',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'time_spent' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who made this attempt
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the content (polymorphic relationship)
     */
    public function content()
    {
        return $this->morphTo();
    }

    /**
     * Get user answers for this attempt
     */
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    /**
     * Calculate percentage score
     */
    public function getPercentageAttribute()
    {
        if ($this->max_score == 0) {
            return 0;
        }
        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Check if attempt is completed
     */
    public function isCompleted()
    {
        return !is_null($this->completed_at);
    }

    /**
     * Scope for completed attempts
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope by module type
     */
    public function scopeByModule($query, $moduleType)
    {
        return $query->where('module_type', $moduleType);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}