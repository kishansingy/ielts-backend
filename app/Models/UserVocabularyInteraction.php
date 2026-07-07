<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVocabularyInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vocabulary_word_id',
        'daily_vocabulary_notification_id',
        'interaction_type',
        'interacted_at',
        'metadata'
    ];

    protected $casts = [
        'interacted_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vocabulary word
     */
    public function vocabularyWord(): BelongsTo
    {
        return $this->belongsTo(VocabularyWord::class);
    }

    /**
     * Get the daily notification
     */
    public function dailyVocabularyNotification(): BelongsTo
    {
        return $this->belongsTo(DailyVocabularyNotification::class);
    }

    /**
     * Scope for interaction type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for user interactions
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}