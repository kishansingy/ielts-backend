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
        Schema::create('daily_vocabulary_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('vocabulary_word_id');

            $table->date('notification_date');

            $table->enum('status', ['pending', 'sent', 'failed'])
                ->default('pending');

            $table->json('target_audience')->nullable();

            $table->integer('total_recipients')->default(0);
            $table->integer('successful_sends')->default(0);
            $table->integer('failed_sends')->default(0);

            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['vocabulary_word_id', 'notification_date'],
                'dvn_word_date_unique'
            );

            $table->index('notification_date');
            $table->index('status');

            $table->foreign('vocabulary_word_id')
                ->references('id')
                ->on('vocabulary_words')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_vocabulary_notifications');
    }
};