<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'submission_type',
        'content',
        'file_path',
        'ai_feedback',
        'score',
        'submitted_at',
    ];

    protected $casts = [
        'ai_feedback' => 'array',
        'score' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the user who made this submission
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task (polymorphic relationship)
     */
    public function task()
    {
        if ($this->submission_type === 'writing') {
            return $this->belongsTo(WritingTask::class, 'task_id');
        } elseif ($this->submission_type === 'speaking') {
            return $this->belongsTo(SpeakingPrompt::class, 'task_id');
        }
        
        return null;
    }

    /**
     * Get file URL for speaking submissions
     */
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    /**
     * Check if submission has AI feedback
     */
    public function hasAiFeedback()
    {
        return !empty($this->ai_feedback);
    }

    /**
     * Get word count for writing submissions
     */
    public function getWordCountAttribute()
    {
        if ($this->submission_type === 'writing' && $this->content) {
            return str_word_count(strip_tags($this->content));
        }
        return 0;
    }

    /**
     * Scope by submission type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('submission_type', $type);
    }

    /**
     * Scope for writing submissions
     */
    public function scopeWriting($query)
    {
        return $query->where('submission_type', 'writing');
    }

    /**
     * Scope for speaking submissions
     */
    public function scopeSpeaking($query)
    {
        return $query->where('submission_type', 'speaking');
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}