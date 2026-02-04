<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockTestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mock_test_id',
        'started_at',
        'completed_at',
        'time_spent',
        'total_score',
        'reading_score',
        'writing_score',
        'listening_score',
        'speaking_score',
        'overall_band',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mockTest()
    {
        return $this->belongsTo(MockTest::class);
    }
}
