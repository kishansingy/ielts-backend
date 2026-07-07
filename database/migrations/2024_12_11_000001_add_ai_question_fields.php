<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiQuestionFields extends Migration
{
    public function up()
    {
        // Add fields to questions table for AI generation and tracking
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('ielts_band_level', ['6', '7', '8', '9'])->nullable()->after('points');
            $table->boolean('is_ai_generated')->default(false)->after('ielts_band_level');
            $table->json('ai_metadata')->nullable()->after('is_ai_generated'); // Store AI generation details
            $table->integer('usage_count')->default(0)->after('ai_metadata'); // Track how many times used
            $table->timestamp('last_used_at')->nullable()->after('usage_count');
            $table->boolean('is_retired')->default(false)->after('last_used_at'); // Mark as retired after use
        });

        // Create question_usage_tracking table
        Schema::create('question_usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mock_test_attempt_id')->constrained('mock_test_attempts')->onDelete('cascade');
            $table->timestamp('used_at');
            $table->timestamps();
            
            $table->unique(['question_id', 'user_id'], 'unique_question_user');
        });

        // Create ai_question_generation_log table
        Schema::create('ai_question_generation_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mock_test_id')->constrained('mock_tests')->onDelete('cascade');
            $table->enum('module_type', ['reading', 'writing', 'listening', 'speaking']);
            $table->enum('ielts_band_level', ['6', '7', '8', '9']);
            $table->integer('questions_requested');
            $table->integer('questions_generated');
            $table->json('generation_metadata')->nullable(); // Store AI response details
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_question_generation_log');
        Schema::dropIfExists('question_usage_tracking');
        
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'ielts_band_level',
                'is_ai_generated', 
                'ai_metadata',
                'usage_count',
                'last_used_at',
                'is_retired'
            ]);
        });
    }
}