<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'question_type',
        'user_answer',
        'is_correct',
        'points_earned',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
    ];

    /**
     * Get the attempt this answer belongs to
     */
    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * Get the question (polymorphic relationship)
     */
    public function question()
    {
        if ($this->question_type === 'reading') {
            return $this->belongsTo(Question::class, 'question_id');
        } elseif ($this->question_type === 'listening') {
            return $this->belongsTo(ListeningQuestion::class, 'question_id');
        }
        
        return null;
    }

    /**
     * Scope for correct answers
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope for incorrect answers
     */
    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Scope by question type
     */
    public function scopeByQuestionType($query, $type)
    {
        return $query->where('question_type', $type);
    }
}