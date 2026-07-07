<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\Question;
use App\Models\ListeningQuestion;
use Illuminate\Support\Facades\DB;

class FixMockTestQuestionsSeeder extends Seeder
{
    /**
     * Delete generic questions and add proper contextual questions
     */
    public function run(): void
    {
        $this->command->info('Fixing mock test questions...');
        
        DB::transaction(function () {
            // Delete all existing questions for mock test passages
            $this->command->info('Removing generic questions...');
            
            $deletedReadingQuestions = Question::whereHas('passage', function($query) {
                $query->where('title', 'like', '%Test%');
            })->delete();
            
            $this->command->info("Deleted {$deletedReadingQuestions} reading questions.");
            
            $deletedListeningQuestions = ListeningQuestion::whereHas('listeningExercise', function($query) {
                $query->where('title', 'like', '%Test%');
            })->delete();
            
            $this->command->info("Deleted {$deletedListeningQuestions} listening questions.");
            
            // Add proper questions to all mock test passages
            $this->command->info('Adding contextual questions...');
            
            $passages = ReadingPassage::where('title', 'like', '%Test%')->get();
            foreach ($passages as $passage) {
                $this->createQuestionsForPassage($passage);
            }
            
            $this->command->info("Added questions to {$passages->count()} reading passages.");
            
            $exercises = ListeningExercise::where('title', 'like', '%Test%')->get();
            foreach ($exercises as $exercise) {
                $this->createQuestionsForListening($exercise);
            }
            
            $this->command->info("Added questions to {$exercises->count()} listening exercises.");
        });
        
        $this->command->info('Mock test questions fixed successfully!');
    }
    
    /**
     * Create questions for a reading passage
     */
    private function createQuestionsForPassage($passage)
    {
        // Create contextual questions based on AI and technology theme
        $questions = [
            ['text' => 'What is the main subject discussed in the passage?', 'type' => 'fill_blank', 'answer' => 'artificial intelligence'],
            ['text' => 'Which industries have been transformed by machine learning according to the passage?', 'type' => 'multiple_choice', 'answer' => 'healthcare and finance', 'options' => ['education and retail', 'healthcare and finance', 'agriculture and mining', 'tourism and hospitality']],
            ['text' => 'Machine learning algorithms enable more accurate _____ and automated decision-making.', 'type' => 'fill_blank', 'answer' => 'predictions'],
            ['text' => 'The passage states that machine learning has transformed which aspect of industries?', 'type' => 'multiple_choice', 'answer' => 'decision-making processes', 'options' => ['marketing strategies', 'decision-making processes', 'employee training', 'product design']],
            ['text' => 'According to the text, what has had an impact on modern society?', 'type' => 'fill_blank', 'answer' => 'artificial intelligence'],
            ['text' => 'The passage mentions that predictions have become more what?', 'type' => 'multiple_choice', 'answer' => 'accurate', 'options' => ['expensive', 'accurate', 'complex', 'time-consuming']],
            ['text' => 'Complete the sentence: The evolution of AI has transformed industries from _____ to finance.', 'type' => 'fill_blank', 'answer' => 'healthcare'],
            ['text' => 'What type of processes does the passage mention have been automated?', 'type' => 'multiple_choice', 'answer' => 'decision-making', 'options' => ['manufacturing', 'decision-making', 'communication', 'transportation']],
            ['text' => 'The passage discusses the evolution of which technology?', 'type' => 'fill_blank', 'answer' => 'artificial intelligence'],
            ['text' => 'According to the passage, machine learning algorithms have enabled what in various industries?', 'type' => 'multiple_choice', 'answer' => 'transformation', 'options' => ['stagnation', 'transformation', 'reduction', 'elimination']],
        ];
        
        $bandLevel = str_replace('band', '', $passage->band_level);
        
        foreach ($questions as $index => $questionData) {
            Question::create([
                'passage_id' => $passage->id,
                'question_text' => $questionData['text'],
                'question_type' => $questionData['type'],
                'correct_answer' => $questionData['answer'],
                'options' => $questionData['options'] ?? null,
                'points' => 1,
                'ielts_band_level' => $bandLevel,
                'is_ai_generated' => false,
            ]);
        }
    }
    
    /**
     * Create questions for a listening exercise
     */
    private function createQuestionsForListening($exercise)
    {
        $questions = [
            ['text' => 'What is the main purpose of this conversation?', 'type' => 'fill_blank', 'answer' => 'course registration'],
            ['text' => 'Who is the student speaking with?', 'type' => 'multiple_choice', 'answer' => 'university administrator', 'options' => ['professor', 'university administrator', 'counselor', 'librarian']],
            ['text' => 'What does the student need assistance with?', 'type' => 'fill_blank', 'answer' => 'registering for courses'],
            ['text' => 'The conversation takes place at a university.', 'type' => 'true_false', 'answer' => 'true', 'options' => ['true', 'false']],
            ['text' => 'What information does the administrator request?', 'type' => 'fill_blank', 'answer' => 'student file'],
            ['text' => 'Which program is the student enrolled in?', 'type' => 'multiple_choice', 'answer' => 'to be determined', 'options' => ['business', 'to be determined', 'engineering', 'arts']],
            ['text' => 'The administrator needs to access the student\'s _____.', 'type' => 'fill_blank', 'answer' => 'records'],
            ['text' => 'What is the student trying to do?', 'type' => 'multiple_choice', 'answer' => 'register for next semester', 'options' => ['drop a course', 'register for next semester', 'change major', 'apply for graduation']],
            ['text' => 'The conversation is about academic _____.', 'type' => 'fill_blank', 'answer' => 'registration'],
            ['text' => 'The administrator offers to help the student.', 'type' => 'true_false', 'answer' => 'true', 'options' => ['true', 'false']],
        ];
        
        foreach ($questions as $index => $questionData) {
            ListeningQuestion::create([
                'listening_exercise_id' => $exercise->id,
                'question_text' => $questionData['text'],
                'question_type' => $questionData['type'],
                'correct_answer' => $questionData['answer'],
                'options' => $questionData['options'] ?? null,
                'points' => 1,
            ]);
        }
    }
}
