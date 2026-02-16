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
        'mobile',
        'password',
        'role',
        'band_level',
        'school_name',
        'is_active',
        'mobile_verified_at',
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
        'mobile_verified_at' => 'datetime',
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
     * Get user's band level
     */
    public function getBandLevel()
    {
        return $this->band_level;
    }

    /**
     * Check if user has specific band level
     */
    public function hasBandLevel($bandLevel)
    {
        return $this->band_level === $bandLevel;
    }

    /**
     * Check if user can access content of specific band level
     */
    public function canAccessBandLevel($contentBandLevel)
    {
        if ($this->isAdmin()) {
            return true; // Admins can access all levels
        }

        // If user has no band level, allow access to band6 content
        $userBandLevel = $this->band_level ?? 'band6';

        return $userBandLevel === $contentBandLevel;
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
     * Check if user is active
     */
    public function isActive()
    {
        return $this->is_active;
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
     * Scope for users by band level
     */
    public function scopeByBandLevel($query, $bandLevel)
    {
        return $query->where('band_level', $bandLevel);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users by school
     */
    public function scopeBySchool($query, $schoolName)
    {
        return $query->where('school_name', $schoolName);
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

    /**
     * Get user's mock test attempts
     */
    public function mockTestAttempts()
    {
        return $this->hasMany(MockTestAttempt::class);
    }

    /**
     * Get user's question usage tracking
     */
    public function questionUsageTracking()
    {
        return $this->hasMany(QuestionUsageTracking::class);
    }

    /**
     * Get user's AI generation logs
     */
    public function aiGenerationLogs()
    {
        return $this->hasMany(AiQuestionGenerationLog::class);
    }

    /**
     * Get the student profile for this user
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Check if user has a student profile
     */
    public function hasStudentProfile()
    {
        return $this->student()->exists();
    }

    /**
     * Get student's band level (from student profile if exists, otherwise from user table)
     */
    public function getStudentBandLevel()
    {
        if ($this->hasStudentProfile()) {
            return $this->student->band_level;
        }
        return $this->band_level;
    }
}
