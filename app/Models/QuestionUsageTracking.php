<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionUsageTracking extends Model
{
    use HasFactory;

    protected $table = 'question_usage_tracking';

    protected $fillable = [
        'question_id',
        'user_id',
        'mock_test_attempt_id',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mockTestAttempt()
    {
        return $this->belongsTo(MockTestAttempt::class);
    }
}