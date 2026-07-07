<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vocabulary_words', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->text('meaning');
            $table->text('example_sentence');
            $table->string('pronunciation')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->string('word_type')->nullable(); // noun, verb, adjective, etc.
            $table->string('oxford_url')->nullable(); // Oxford dictionary link
            $table->json('synonyms')->nullable(); // Array of synonyms
            $table->json('antonyms')->nullable(); // Array of antonyms
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority words sent first
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
            $table->index('difficulty_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocabulary_words');
    }
};