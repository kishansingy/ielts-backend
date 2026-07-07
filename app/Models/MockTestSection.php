<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockTestSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'mock_test_id',
        'module_type',
        'content_id',
        'content_type',
        'order',
        'duration_minutes',
    ];

    public function mockTest()
    {
        return $this->belongsTo(MockTest::class);
    }

    public function content()
    {
        return $this->morphTo();
    }
}
