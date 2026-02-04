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
        'band_level',
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

    /**
     * Scope for band level
     */
    public function scopeByBandLevel($query, $level)
    {
        return $query->where('band_level', $level);
    }

    /**
     * Get band level display name
     */
    public function getBandLevelDisplay()
    {
        $levels = [
            'band6' => 'Band 6',
            'band7' => 'Band 7',
            'band8' => 'Band 8',
            'band9' => 'Band 9'
        ];

        return $levels[$this->band_level] ?? 'No Band Assigned';
    }
}