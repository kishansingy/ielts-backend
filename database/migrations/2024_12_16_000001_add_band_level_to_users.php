<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBandLevelToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('band_level', ['band6', 'band7', 'band8', 'band9'])->nullable()->after('role');
            $table->string('school_name')->nullable()->after('band_level');
            $table->boolean('is_active')->default(true)->after('school_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['band_level', 'school_name', 'is_active']);
        });
    }
}