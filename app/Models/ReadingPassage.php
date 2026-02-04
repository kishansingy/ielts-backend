<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingPassage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'difficulty_level',
        'band_level',
        'time_limit',
        'created_by',
    ];

    protected $casts = [
        'time_limit' => 'integer',
    ];

    /**
     * Get the user who created this passage
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions for this passage
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'passage_id');
    }

    /**
     * Get attempts for this passage
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