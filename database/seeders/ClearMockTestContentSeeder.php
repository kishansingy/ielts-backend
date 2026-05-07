<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Clears ALL mock test content and questions.
 * Run BEFORE AIGeneratedMockTestSeeder.
 *
 * php artisan db:seed --class=ClearMockTestContentSeeder
 */
class ClearMockTestContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Clearing all mock test content...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Attempt data
        DB::table('mock_test_attempts')->truncate();
        $this->command->line('  ✓ mock_test_attempts cleared');

        // Sections linking tests to content
        DB::table('mock_test_sections')->truncate();
        $this->command->line('  ✓ mock_test_sections cleared');

        // Mock tests themselves
        DB::table('mock_tests')->truncate();
        $this->command->line('  ✓ mock_tests cleared');

        // Questions and usage tracking
        DB::table('question_usage_tracking')->truncate();
        DB::table('questions')->truncate();
        $this->command->line('  ✓ questions cleared');

        // Reading passages
        DB::table('reading_passages')->truncate();
        $this->command->line('  ✓ reading_passages cleared');

        // Listening
        DB::table('listening_questions')->truncate();
        DB::table('listening_exercises')->truncate();
        $this->command->line('  ✓ listening_exercises + questions cleared');

        // Writing tasks
        DB::table('writing_tasks')->truncate();
        $this->command->line('  ✓ writing_tasks cleared');

        // Speaking prompts
        DB::table('speaking_prompts')->truncate();
        $this->command->line('  ✓ speaking_prompts cleared');

        // Module questions pivot
        DB::table('module_questions')->truncate();
        $this->command->line('  ✓ module_questions cleared');

        // AI generation logs
        DB::table('ai_question_generation_log')->truncate();
        $this->command->line('  ✓ ai_question_generation_logs cleared');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('All mock test content cleared successfully.');
    }
}
