<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'band_level',
        'school_name',
        'class_name',
        'grade_level',
        'student_id_number',
        'date_of_birth',
        'gender',
        'nationality',
        'native_language',
        'mobile_number',
        'alternate_mobile',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'parent_name',
        'parent_email',
        'parent_mobile',
        'parent_relationship',
        'emergency_contact_name',
        'emergency_contact_mobile',
        'emergency_contact_relationship',
        'enrollment_date',
        'target_exam_date',
        'learning_goals',
        'special_requirements',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the student profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active students
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for students by band level
     */
    public function scopeByBandLevel($query, $bandLevel)
    {
        return $query->where('band_level', $bandLevel);
    }

    /**
     * Scope for students by school
     */
    public function scopeBySchool($query, $schoolName)
    {
        return $query->where('school_name', $schoolName);
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
     * Get full name from user
     */
    public function getFullNameAttribute()
    {
        return $this->user->name ?? 'N/A';
    }

    /**
     * Get email from user
     */
    public function getEmailAttribute()
    {
        return $this->user->email ?? 'N/A';
    }

    /**
     * Check if student can access content of specific band level
     */
    public function canAccessBandLevel($contentBandLevel)
    {
        return $this->band_level === $contentBandLevel;
    }

    /**
     * Get age
     */
    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return $this->date_of_birth->age;
    }
}
