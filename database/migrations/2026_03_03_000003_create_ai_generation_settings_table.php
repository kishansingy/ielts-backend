<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiGenerationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_generation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('ai_generation_settings')->insert([
            [
                'key' => 'daily_generation_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable daily automated AI question generation',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'daily_generation_time',
                'value' => '02:00',
                'type' => 'string',
                'description' => 'Time for daily generation (HH:MM format)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'daily_request_limit',
                'value' => '750',
                'type' => 'integer',
                'description' => 'Maximum API requests per day for generation (50% of 1500)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'evaluation_request_limit',
                'value' => '750',
                'type' => 'integer',
                'description' => 'Maximum API requests per day for evaluation (50% of 1500)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'generation_per_module',
                'value' => json_encode([
                    'reading' => 5,
                    'writing' => 5,
                    'speaking' => 5,
                    'listening' => 5
                ]),
                'type' => 'json',
                'description' => 'Number of questions to generate per module per band level',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'enabled_modules',
                'value' => json_encode(['reading', 'writing', 'speaking', 'listening']),
                'type' => 'json',
                'description' => 'Modules enabled for AI generation',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'enabled_band_levels',
                'value' => json_encode(['6', '7', '8', '9']),
                'type' => 'json',
                'description' => 'Band levels enabled for AI generation',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'rate_limit_delay',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Delay in seconds between API requests (minimum 5 for rate limiting)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_generation_settings');
    }
}
