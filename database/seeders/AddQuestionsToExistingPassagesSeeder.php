<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\Question;
use App\Models\ListeningQuestion;

class AddQuestionsToExistingPassagesSeeder extends Seeder
{
    /**
     * Add questions to existing passages that don't have any
     */
    public function run(): void
    {
        $this->command->info('Adding questions to existing reading passages...');
        
        // Get all reading passages without questions
        $passages = ReadingPassage::doesntHave('questions')->get();
        
        foreach ($passages as $passage) {
            $this->createQuestionsForPassage($passage);
        }
        
        $this->command->info("Added questions to {$passages->count()} reading passages.");
        
        // Get all listening exercises without questions
        $this->command->info('Adding questions to existing listening exercises...');
        $exercises = ListeningExercise::doesntHave('questions')->get();
        
        foreach ($exercises as $exercise) {
            $this->createQuestionsForListening($exercise);
        }
        
        $this->command->info("Added questions to {$exercises->count()} listening exercises.");
        $this->command->info('Done!');
    }
    
    /**
     * Create questions for a reading passage
     */
    private function createQuestionsForPassage($passage)
    {
        $questions = [
            ['text' => 'What is the main topic discussed in this passage?', 'type' => 'fill_blank', 'answer' => 'main topic'],
            ['text' => 'According to the passage, what is mentioned?', 'type' => 'multiple_choice', 'answer' => 'key point', 'options' => ['option A', 'key point', 'option C', 'option D']],
            ['text' => 'The passage discusses which concept?', 'type' => 'fill_blank', 'answer' => 'important concept'],
            ['text' => 'What does the author suggest?', 'type' => 'multiple_choice', 'answer' => 'suggestion', 'options' => ['alternative', 'suggestion', 'different view', 'another option']],
            ['text' => 'Fill in the blank: The passage emphasizes _____.', 'type' => 'fill_blank', 'answer' => 'key emphasis'],
            ['text' => 'Which statement is true according to the passage?', 'type' => 'multiple_choice', 'answer' => 'true statement', 'options' => ['false info', 'true statement', 'incorrect', 'wrong']],
            ['text' => 'What is the purpose of this passage?', 'type' => 'fill_blank', 'answer' => 'to inform'],
            ['text' => 'The author mentions which example?', 'type' => 'multiple_choice', 'answer' => 'relevant example', 'options' => ['unrelated', 'relevant example', 'different case', 'other instance']],
            ['text' => 'Complete: The text indicates that _____.', 'type' => 'fill_blank', 'answer' => 'indication'],
            ['text' => 'What conclusion can be drawn?', 'type' => 'multiple_choice', 'answer' => 'logical conclusion', 'options' => ['wrong conclusion', 'logical conclusion', 'unrelated', 'different outcome']],
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
            ['text' => 'What is the main purpose of this audio?', 'type' => 'fill_blank', 'answer' => 'main purpose'],
            ['text' => 'Who is speaking in this recording?', 'type' => 'multiple_choice', 'answer' => 'speaker', 'options' => ['person A', 'speaker', 'person C', 'person D']],
            ['text' => 'What topic is being discussed?', 'type' => 'fill_blank', 'answer' => 'discussion topic'],
            ['text' => 'The speaker mentions which point?', 'type' => 'multiple_choice', 'answer' => 'important point', 'options' => ['unrelated', 'important point', 'different', 'other']],
            ['text' => 'Complete: The speaker says that _____.', 'type' => 'fill_blank', 'answer' => 'statement'],
            ['text' => 'What does the speaker suggest?', 'type' => 'multiple_choice', 'answer' => 'suggestion', 'options' => ['alternative', 'suggestion', 'different', 'other']],
            ['text' => 'Fill in: The main idea is _____.', 'type' => 'fill_blank', 'answer' => 'main idea'],
            ['text' => 'Which statement is correct?', 'type' => 'true_false', 'answer' => 'true', 'options' => ['true', 'false']],
            ['text' => 'What example is given?', 'type' => 'fill_blank', 'answer' => 'example'],
            ['text' => 'The speaker concludes by saying what?', 'type' => 'multiple_choice', 'answer' => 'conclusion', 'options' => ['different', 'conclusion', 'unrelated', 'other']],
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
