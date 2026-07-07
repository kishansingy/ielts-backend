<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiFeedbackToMockTestAttempts extends Migration
{
    public function up()
    {
        Schema::table('mock_test_attempts', function (Blueprint $table) {
            $table->json('ai_feedback')->nullable()->after('overall_band');
        });
    }

    public function down()
    {
        Schema::table('mock_test_attempts', function (Blueprint $table) {
            $table->dropColumn('ai_feedback');
        });
    }
}
