<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMockTestSectionsTable extends Migration
{
    public function up()
    {
        Schema::create('mock_test_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_test_id')->constrained('mock_tests')->onDelete('cascade');
            $table->enum('module_type', ['reading', 'writing', 'listening', 'speaking']);
            $table->unsignedBigInteger('content_id'); // ID of passage/task/exercise/prompt
            $table->string('content_type'); // Model class name
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->nullable(); // Optional section-specific duration
            $table->timestamps();
            
            $table->index(['content_id', 'content_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mock_test_sections');
    }
}
