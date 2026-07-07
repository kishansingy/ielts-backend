<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed production data only (questions and mock tests)
     * Run this seeder in production: php artisan db:seed --class=ProductionSeeder
     */
    public function run(): void
    {
        $this->command->info('Starting production data seeding...');
        $this->command->info('This will create IELTS questions and mock tests for all band levels.');
        
        $this->call([
            ProductionIELTSQuestionsSeeder::class,
            ProductionMockTestsSeeder::class,
        ]);
        
        $this->command->info('');
        $this->command->info('✓ Production seeding completed successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- Created modules for Reading, Listening, Writing, and Speaking');
        $this->command->info('- Added 20 questions per module for each band level (band6, band7, band8, band9)');
        $this->command->info('- Created 20 complete mock tests for each band level');
        $this->command->info('- Each mock test includes all 4 sections: Reading, Listening, Writing, Speaking');
        $this->command->info('');
    }
}
