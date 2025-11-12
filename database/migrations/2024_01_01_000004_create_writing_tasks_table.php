<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWritingTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('writing_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('task_type', ['task1', 'task2']);
            $table->longText('prompt');
            $table->text('instructions')->nullable();
            $table->integer('time_limit')->default(60); // minutes
            $table->integer('word_limit')->default(250);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('writing_tasks');
    }
}