<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBandLevelToContent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add band level to modules
        Schema::table('modules', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6')->after('module_type');
        });

        // Add band level to reading passages
        Schema::table('reading_passages', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6')->after('difficulty_level');
        });

        // Add band level to writing tasks
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6')->after('task_type');
        });

        // Add band level to listening exercises
        Schema::table('listening_exercises', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6')->after('difficulty_level');
        });

        // Add band level to speaking prompts
        Schema::table('speaking_prompts', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6')->after('difficulty_level');
        });

        // Add band level to mock tests
        Schema::table('mock_tests', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->default('band6')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('band_level');
        });

        Schema::table('reading_passages', function (Blueprint $table) {
            $table->dropColumn('band_level');
        });

        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->dropColumn('band_level');
        });

        Schema::table('listening_exercises', function (Blueprint $table) {
            $table->dropColumn('band_level');
        });

        Schema::table('speaking_prompts', function (Blueprint $table) {
            $table->dropColumn('band_level');
        });

        Schema::table('mock_tests', function (Blueprint $table) {
            $table->dropColumn('band_level');
        });
    }
}