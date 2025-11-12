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
}