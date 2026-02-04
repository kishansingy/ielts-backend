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
        Schema::create('user_vocabulary_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vocabulary_word_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('daily_vocabulary_notification_id')->nullable();
            $table->foreign('daily_vocabulary_notification_id', 'uvi_dvn_foreign')->references('id')->on('daily_vocabulary_notifications')->onDelete('set null');
            $table->enum('interaction_type', ['viewed', 'bookmarked', 'practiced', 'mastered'])->default('viewed');
            $table->timestamp('interacted_at');
            $table->json('metadata')->nullable(); // Store additional interaction data
            $table->timestamps();
            
            $table->unique(['user_id', 'vocabulary_word_id', 'interaction_type'], 'uvi_user_word_type_unique');
            $table->index(['user_id', 'interacted_at']);
            $table->index('interaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vocabulary_interactions');
    }
};