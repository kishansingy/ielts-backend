<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'question_id',
        'order',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}