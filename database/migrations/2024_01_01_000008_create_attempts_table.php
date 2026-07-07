<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('module_type', ['reading', 'writing', 'listening', 'speaking']);
            $table->unsignedBigInteger('content_id'); // Polymorphic reference
            $table->string('content_type'); // Model class name
            $table->decimal('score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2);
            $table->integer('time_spent')->default(0); // seconds
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Index for polymorphic relationship
            $table->index(['content_id', 'content_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attempts');
    }
}