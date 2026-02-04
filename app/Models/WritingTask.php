<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WritingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'task_type',
        'prompt',
        'instructions',
        'time_limit',
        'word_limit',
        'band_level',
        'created_by',
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'word_limit' => 'integer',
    ];

    /**
     * Get the user who created this task
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get submissions for this task
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class, 'task_id')
                    ->where('submission_type', 'writing');
    }

    /**
     * Get attempts for this task
     */
    public function attempts()
    {
        return $this->morphMany(Attempt::class, 'content');
    }

    /**
     * Scope for task type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('task_type', $type);
    }

    /**
     * Scope for Task 1
     */
    public function scopeTask1($query)
    {
        return $query->where('task_type', 'task1');
    }

    /**
     * Scope for Task 2
     */
    public function scopeTask2($query)
    {
        return $query->where('task_type', 'task2');
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