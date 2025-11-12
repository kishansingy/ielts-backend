<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is student
     */
    public function isStudent()
    {
        return $this->role === 'student';
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for student users
     */
    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    /**
     * Get user's attempts
     */
    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    /**
     * Get user's submissions
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get content created by this user (admin)
     */
    public function readingPassages()
    {
        return $this->hasMany(ReadingPassage::class, 'created_by');
    }

    public function writingTasks()
    {
        return $this->hasMany(WritingTask::class, 'created_by');
    }

    public function listeningExercises()
    {
        return $this->hasMany(ListeningExercise::class, 'created_by');
    }

    public function speakingPrompts()
    {
        return $this->hasMany(SpeakingPrompt::class, 'created_by');
    }
}
