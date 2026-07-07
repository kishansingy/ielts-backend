<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceColumnToContentTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add source column to reading_passages
        if (Schema::hasTable('reading_passages') && !Schema::hasColumn('reading_passages', 'source')) {
            Schema::table('reading_passages', function (Blueprint $table) {
                $table->string('source')->default('manual')->after('created_by');
            });
        }

        // Add source column to writing_tasks
        if (Schema::hasTable('writing_tasks') && !Schema::hasColumn('writing_tasks', 'source')) {
            Schema::table('writing_tasks', function (Blueprint $table) {
                $table->string('source')->default('manual')->after('created_by');
            });
        }

        // Add source column to speaking_prompts
        if (Schema::hasTable('speaking_prompts') && !Schema::hasColumn('speaking_prompts', 'source')) {
            Schema::table('speaking_prompts', function (Blueprint $table) {
                $table->string('source')->default('manual')->after('created_by');
            });
        }

        // Add source column to listening_exercises
        if (Schema::hasTable('listening_exercises') && !Schema::hasColumn('listening_exercises', 'source')) {
            Schema::table('listening_exercises', function (Blueprint $table) {
                $table->string('source')->default('manual')->after('created_by');
            });
        }

        // Add follow_up_questions to speaking_prompts if not exists
        if (Schema::hasTable('speaking_prompts') && !Schema::hasColumn('speaking_prompts', 'follow_up_questions')) {
            Schema::table('speaking_prompts', function (Blueprint $table) {
                $table->json('follow_up_questions')->nullable();
            });
        }

        // Add evaluation_criteria to writing_tasks if not exists
        if (Schema::hasTable('writing_tasks') && !Schema::hasColumn('writing_tasks', 'evaluation_criteria')) {
            Schema::table('writing_tasks', function (Blueprint $table) {
                $table->json('evaluation_criteria')->nullable();
            });
        }

        // Add model_answer to writing_tasks if not exists
        if (Schema::hasTable('writing_tasks') && !Schema::hasColumn('writing_tasks', 'model_answer')) {
            Schema::table('writing_tasks', function (Blueprint $table) {
                $table->text('model_answer')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('reading_passages', 'source')) {
            Schema::table('reading_passages', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasColumn('writing_tasks', 'source')) {
            Schema::table('writing_tasks', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasColumn('speaking_prompts', 'source')) {
            Schema::table('speaking_prompts', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasColumn('listening_exercises', 'source')) {
            Schema::table('listening_exercises', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasColumn('speaking_prompts', 'follow_up_questions')) {
            Schema::table('speaking_prompts', function (Blueprint $table) {
                $table->dropColumn('follow_up_questions');
            });
        }

        if (Schema::hasColumn('writing_tasks', 'evaluation_criteria')) {
            Schema::table('writing_tasks', function (Blueprint $table) {
                $table->dropColumn('evaluation_criteria');
            });
        }

        if (Schema::hasColumn('writing_tasks', 'model_answer')) {
            Schema::table('writing_tasks', function (Blueprint $table) {
                $table->dropColumn('model_answer');
            });
        }
    }
}
