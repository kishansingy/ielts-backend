<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiQuestionGenerationLog extends Model
{
    use HasFactory;

    protected $table = 'ai_question_generation_log';

    protected $fillable = [
        'user_id',
        'mock_test_id',
        'module_type',
        'ielts_band_level',
        'questions_requested',
        'questions_generated',
        'generation_metadata',
        'generated_at',
    ];

    protected $casts = [
        'generation_metadata' => 'array',
        'generated_at' => 'datetime',
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