<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'prompt_text',
        'preparation_time',
        'response_time',
        'difficulty_level',
        'created_by',
    ];

    protected $casts = [
        'preparation_time' => 'integer',
        'response_time' => 'integer',
    ];

    /**
     * Get the user who created this prompt
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get submissions for this prompt
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class, 'task_id')
                    ->where('submission_type', 'speaking');
    }

    /**
     * Get attempts for this prompt
     */
    public function attempts()
    {
        return $this->morphMany(Attempt::class, 'content');
    }

    /**
     * Scope for difficulty level
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }
}