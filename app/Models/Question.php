<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'passage_id',
        'question_text',
        'question_type',
        'correct_answer',
        'options',
        'points',
        'ielts_band_level',
        'is_ai_generated',
        'ai_metadata',
        'usage_count',
        'last_used_at',
        'is_retired',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'is_ai_generated' => 'boolean',
        'ai_metadata' => 'array',
        'last_used_at' => 'datetime',
        'is_retired' => 'boolean',
    ];

    /**
     * Get the reading passage this question belongs to
     */
    public function passage()
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }

    /**
     * Get user answers for this question
     */
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'question_id')
                    ->where('question_type', 'reading');
    }

    /**
     * Check if an answer is correct
     */
    public function isCorrectAnswer($answer)
    {
        return strtolower(trim($answer)) === strtolower(trim($this->correct_answer));
    }

    /**
     * Scope for question type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('question_type', $type);
    }

    /**
     * Scope for IELTS band level
     */
    public function scopeByBandLevel($query, $level)
    {
        return $query->where('ielts_band_level', $level);
    }

    /**
     * Scope for available questions (not retired)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_retired', false);
    }

    /**
     * Scope for unused questions by user
     */
    public function scopeUnusedByUser($query, $userId)
    {
        return $query->whereDoesntHave('usageTracking', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Get usage tracking records
     */
    public function usageTracking()
    {
        return $this->hasMany(QuestionUsageTracking::class);
    }

    /**
     * Get modules this question belongs to
     */
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'module_questions')
                    ->withPivot('order')
                    ->withTimestamps();
    }
}