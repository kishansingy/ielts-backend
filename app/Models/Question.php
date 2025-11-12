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
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
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
}