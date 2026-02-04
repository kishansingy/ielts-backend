<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMockTestAttemptsTable extends Migration
{
    public function up()
    {
        Schema::create('mock_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mock_test_id')->constrained('mock_tests')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent')->default(0); // seconds
            $table->decimal('total_score', 5, 2)->default(0);
            $table->decimal('reading_score', 5, 2)->default(0);
            $table->decimal('writing_score', 5, 2)->default(0);
            $table->decimal('listening_score', 5, 2)->default(0);
            $table->decimal('speaking_score', 5, 2)->default(0);
            $table->decimal('overall_band', 3, 1)->default(0); // IELTS band score
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mock_test_attempts');
    }
}
