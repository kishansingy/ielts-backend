<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VocabularyWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'meaning',
        'example_sentence',
        'pronunciation',
        'difficulty_level',
        'word_type',
        'oxford_url',
        'synonyms',
        'antonyms',
        'is_active',
        'priority'
    ];

    protected $casts = [
        'synonyms' => 'array',
        'antonyms' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer'
    ];

    /**
     * Get daily notifications for this word
     */
    public function dailyNotifications(): HasMany
    {
        return $this->hasMany(DailyVocabularyNotification::class);
    }

    /**
     * Get user interactions for this word
     */
    public function userInteractions(): HasMany
    {
        return $this->hasMany(UserVocabularyInteraction::class);
    }

    /**
     * Scope for active words
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for difficulty level
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope for words not sent today
     */
    public function scopeNotSentToday($query)
    {
        return $query->whereDoesntHave('dailyNotifications', function ($q) {
            $q->where('notification_date', today())
              ->where('status', 'sent');
        });
    }

    /**
     * Get Oxford dictionary URL
     */
    public function getOxfordUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }
        
        // Generate Oxford URL if not provided
        $word = strtolower(str_replace(' ', '-', $this->word));
        return "https://www.oxfordlearnersdictionaries.com/definition/english/{$word}";
    }
}