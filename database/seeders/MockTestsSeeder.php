<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\ReadingPassage;
use App\Models\WritingTask;
use App\Models\ListeningExercise;
use App\Models\SpeakingPrompt;
use Illuminate\Support\Facades\DB;

class MockTestsSeeder extends Seeder
{
    private $bandLevels = ['band6', 'band7', 'band8', 'band9'];
    private $testsPerBand = 20; // Create 20 mock tests per band level
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Mock Tests...');
        
        // Clear existing mock tests
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MockTestSection::truncate();
        MockTest::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        foreach ($this->bandLevels as $bandLevel) {
            $this->command->info("  Creating {$this->testsPerBand} mock tests for {$bandLevel}...");
            
            // Get available content for this band level
            $readingPassages = ReadingPassage::where('band_level', $bandLevel)->get();
            $writingTasks = WritingTask::where('band_level', $bandLevel)->get();
            $listeningExercises = ListeningExercise::where('band_level', $bandLevel)->get();
            $speakingPrompts = SpeakingPrompt::where('band_level', $bandLevel)->get();
            
            if ($readingPassages->isEmpty() || $writingTasks->isEmpty() || 
                $listeningExercises->isEmpty() || $speakingPrompts->isEmpty()) {
                $this->command->warn("  Skipping {$bandLevel} - insufficient content");
                continue;
            }
            
            for ($i = 1; $i <= $this->testsPerBand; $i++) {
                // Create mock test
                $mockTest = MockTest::create([
                    'title' => "IELTS Mock Test {$i} - " . strtoupper(str_replace('band', 'BAND', $bandLevel)),
                    'description' => "Complete IELTS practice test {$i} for {$bandLevel} level. Includes Reading, Listening, Writing, and Speaking sections.",
                    'band_level' => $bandLevel,
                    'duration_minutes' => 180, // 3 hours total
                    'is_active' => true,
                    'available_from' => now(),
                    'available_until' => null,
                ]);
                
                // Select content for this test (rotate through available content)
                $readingIndex = ($i - 1) % $readingPassages->count();
                $listeningIndex = ($i - 1) % $listeningExercises->count();
                $writingTask1Index = ($i - 1) % $writingTasks->where('task_type', 'task1')->count();
                $writingTask2Index = ($i - 1) % $writingTasks->where('task_type', 'task2')->count();
                $speakingIndex = ($i - 1) % $speakingPrompts->count();
                
                $readingPassage = $readingPassages[$readingIndex];
                $listeningExercise = $listeningExercises[$listeningIndex];
                $writingTask1 = $writingTasks->where('task_type', 'task1')->values()[$writingTask1Index];
                $writingTask2 = $writingTasks->where('task_type', 'task2')->values()[$writingTask2Index];
                $speakingPrompt = $speakingPrompts[$speakingIndex];
                
                // Create sections in IELTS order: Reading, Listening, Writing, Speaking
                
                // 1. Reading Section (2 passages)
                MockTestSection::create([
                    'mock_test_id' => $mockTest->id,
                    'module_type' => 'reading',
                    'content_id' => $readingPassage->id,
                    'content_type' => 'App\Models\ReadingPassage',
                    'order' => 1,
                    'duration_minutes' => 20,
                ]);
                
                // Add second reading passage if available
                $readingIndex2 = ($i) % $readingPassages->count();
                if ($readingIndex2 !== $readingIndex && $readingPassages->count() > 1) {
                    MockTestSection::create([
                        'mock_test_id' => $mockTest->id,
                        'module_type' => 'reading',
                        'content_id' => $readingPassages[$readingIndex2]->id,
                        'content_type' => 'App\Models\ReadingPassage',
                        'order' => 2,
                        'duration_minutes' => 20,
                    ]);
                }
                
                // 2. Listening Section (2 exercises)
                MockTestSection::create([
                    'mock_test_id' => $mockTest->id,
                    'module_type' => 'listening',
                    'content_id' => $listeningExercise->id,
                    'content_type' => 'App\Models\ListeningExercise',
                    'order' => 3,
                    'duration_minutes' => 15,
                ]);
                
                // Add second listening exercise if available
                $listeningIndex2 = ($i) % $listeningExercises->count();
                if ($listeningIndex2 !== $listeningIndex && $listeningExercises->count() > 1) {
                    MockTestSection::create([
                        'mock_test_id' => $mockTest->id,
                        'module_type' => 'listening',
                        'content_id' => $listeningExercises[$listeningIndex2]->id,
                        'content_type' => 'App\Models\ListeningExercise',
                        'order' => 4,
                        'duration_minutes' => 15,
                    ]);
                }
                
                // 3. Writing Section (Task 1)
                MockTestSection::create([
                    'mock_test_id' => $mockTest->id,
                    'module_type' => 'writing',
                    'content_id' => $writingTask1->id,
                    'content_type' => 'App\Models\WritingTask',
                    'order' => 5,
                    'duration_minutes' => 20,
                ]);
                
                // 4. Writing Section (Task 2)
                MockTestSection::create([
                    'mock_test_id' => $mockTest->id,
                    'module_type' => 'writing',
                    'content_id' => $writingTask2->id,
                    'content_type' => 'App\Models\WritingTask',
                    'order' => 6,
                    'duration_minutes' => 40,
                ]);
                
                // 5. Speaking Section (3 parts)
                MockTestSection::create([
                    'mock_test_id' => $mockTest->id,
                    'module_type' => 'speaking',
                    'content_id' => $speakingPrompt->id,
                    'content_type' => 'App\Models\SpeakingPrompt',
                    'order' => 7,
                    'duration_minutes' => 15,
                ]);
                
                // Add more speaking prompts if available
                $speakingIndex2 = ($i) % $speakingPrompts->count();
                if ($speakingIndex2 !== $speakingIndex && $speakingPrompts->count() > 1) {
                    MockTestSection::create([
                        'mock_test_id' => $mockTest->id,
                        'module_type' => 'speaking',
                        'content_id' => $speakingPrompts[$speakingIndex2]->id,
                        'content_type' => 'App\Models\SpeakingPrompt',
                        'order' => 8,
                        'duration_minutes' => 10,
                    ]);
                }
            }
        }
        
        $totalTests = MockTest::count();
        $totalSections = MockTestSection::count();
        
        $this->command->info("Mock Tests created successfully!");
        $this->command->info("Total Mock Tests: {$totalTests}");
        $this->command->info("Total Sections: {$totalSections}");
        $this->command->info("Average sections per test: " . round($totalSections / $totalTests, 1));
    }
}
