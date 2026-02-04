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
        'band_level',
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

    /**
     * Get audio file URL
     */
    public function getAudioUrlAttribute()
    {
        return asset('storage/' . $this->audio_file_path);
    }
}