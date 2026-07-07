<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateModulesForAi extends Migration
{
    public function up()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->enum('module_type', ['reading', 'writing', 'listening', 'speaking'])->after('name');
            $table->json('ai_generation_config')->nullable()->after('description'); // Store AI prompts and configs
            $table->boolean('supports_ai_generation')->default(false)->after('ai_generation_config');
        });

        // Create module_questions pivot table for better organization
        Schema::create('module_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['module_id', 'question_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('module_questions');
        
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'module_type',
                'ai_generation_config',
                'supports_ai_generation'
            ]);
        });
    }
}