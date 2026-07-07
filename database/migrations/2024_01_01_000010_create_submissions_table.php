<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('task_id'); // References writing_tasks or speaking_prompts
            $table->enum('submission_type', ['writing', 'speaking']);
            $table->longText('content')->nullable(); // For writing submissions
            $table->string('file_path')->nullable(); // For speaking audio files
            $table->json('ai_feedback')->nullable(); // AI analysis results
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            
            // Index for task polymorphic relationship
            $table->index(['task_id', 'submission_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submissions');
    }
}