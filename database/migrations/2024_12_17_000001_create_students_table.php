<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Band Level
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6');
            
            // School Information
            $table->string('school_name')->nullable();
            $table->string('class_name')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('student_id_number')->nullable()->unique();
            
            // Personal Information
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('native_language')->nullable();
            
            // Contact Information
            $table->string('mobile_number')->nullable();
            $table->string('alternate_mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            
            // Parent/Guardian Information
            $table->string('parent_name')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('parent_mobile')->nullable();
            $table->string('parent_relationship')->nullable(); // Father, Mother, Guardian
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_mobile')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // Academic Information
            $table->date('enrollment_date')->nullable();
            $table->string('target_exam_date')->nullable();
            $table->text('learning_goals')->nullable();
            $table->text('special_requirements')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Admin notes
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('band_level');
            $table->index('school_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
}
