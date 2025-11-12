<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListeningExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'audio_file_path',
        'transcript',
        'duration',
        'difficulty_level',
        'created_by',
    ];

    protected $casts = [
        'duration' => 'integer',
    ];

    /**
     * Get the user who created this exercise
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions for this exercise
     */
    public function questions()
    {
        return $this->hasMany(ListeningQuestion::class, 'listening_exercise_id');
    }

    /**
     * Get attempts for this exercise
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
     * Get audio file URL
     */
    public function getAudioUrlAttribute()
    {
        return asset('storage/' . $this->audio_file_path);
    }
}