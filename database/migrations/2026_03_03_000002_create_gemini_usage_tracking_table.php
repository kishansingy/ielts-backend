<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeminiUsageTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gemini_usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('model_used');
            $table->string('module_type'); // reading, writing, speaking, listening
            $table->string('band_level');
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->text('request_type')->nullable(); // generation, evaluation
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at');
            $table->timestamps();
            
            $table->index(['requested_at', 'module_type']);
            $table->index('model_used');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gemini_usage_tracking');
    }
}
