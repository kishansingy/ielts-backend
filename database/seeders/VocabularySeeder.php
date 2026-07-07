<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VocabularyWord;

class VocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating vocabulary words...');

        // Create specific high-quality words first
        $specificWords = [
            [
                'word' => 'Abundant',
                'meaning' => 'Existing or available in large quantities; plentiful',
                'example_sentence' => 'The region has abundant natural resources that support its economy.',
                'pronunciation' => 'əˈbʌndənt',
                'difficulty_level' => 'intermediate',
                'word_type' => 'adjective',
                'synonyms' => ['plentiful', 'copious', 'ample', 'profuse'],
                'antonyms' => ['scarce', 'sparse', 'limited', 'insufficient'],
                'priority' => 95
            ],
            [
                'word' => 'Meticulous',
                'meaning' => 'Showing great attention to detail; very careful and precise',
                'example_sentence' => 'She was meticulous in her research, checking every source twice.',
                'pronunciation' => 'mɪˈtɪkjʊləs',
                'difficulty_level' => 'advanced',
                'word_type' => 'adjective',
                'synonyms' => ['careful', 'thorough', 'precise', 'scrupulous'],
                'antonyms' => ['careless', 'sloppy', 'hasty', 'negligent'],
                'priority' => 90
            ],
            [
                'word' => 'Resilient',
                'meaning' => 'Able to withstand or recover quickly from difficult conditions',
                'example_sentence' => 'The resilient community rebuilt their town after the natural disaster.',
                'pronunciation' => 'rɪˈzɪliənt',
                'difficulty_level' => 'intermediate',
                'word_type' => 'adjective',
                'synonyms' => ['tough', 'strong', 'flexible', 'adaptable'],
                'antonyms' => ['fragile', 'weak', 'brittle', 'vulnerable'],
                'priority' => 88
            ],
            [
                'word' => 'Innovative',
                'meaning' => 'Featuring new methods; advanced and original',
                'example_sentence' => 'The company\'s innovative approach to marketing attracted many customers.',
                'pronunciation' => 'ˈɪnəveɪtɪv',
                'difficulty_level' => 'intermediate',
                'word_type' => 'adjective',
                'synonyms' => ['creative', 'original', 'inventive', 'pioneering'],
                'antonyms' => ['traditional', 'conventional', 'outdated', 'conservative'],
                'priority' => 87
            ],
            [
                'word' => 'Comprehensive',
                'meaning' => 'Complete and including everything that is necessary',
                'example_sentence' => 'The comprehensive report covered all aspects of the environmental impact.',
                'pronunciation' => 'ˌkɒmprɪˈhensɪv',
                'difficulty_level' => 'advanced',
                'word_type' => 'adjective',
                'synonyms' => ['complete', 'thorough', 'extensive', 'all-inclusive'],
                'antonyms' => ['incomplete', 'partial', 'limited', 'superficial'],
                'priority' => 86
            ],
            [
                'word' => 'Sustainable',
                'meaning' => 'Able to be maintained at a certain rate or level without depleting resources',
                'example_sentence' => 'The government promotes sustainable development to protect the environment.',
                'pronunciation' => 'səˈsteɪnəbl',
                'difficulty_level' => 'intermediate',
                'word_type' => 'adjective',
                'synonyms' => ['maintainable', 'viable', 'renewable', 'eco-friendly'],
                'antonyms' => ['unsustainable', 'depleting', 'wasteful', 'harmful'],
                'priority' => 89
            ],
            [
                'word' => 'Eloquent',
                'meaning' => 'Fluent or persuasive in speaking or writing',
                'example_sentence' => 'The politician gave an eloquent speech that moved the audience.',
                'pronunciation' => 'ˈeləkwənt',
                'difficulty_level' => 'advanced',
                'word_type' => 'adjective',
                'synonyms' => ['articulate', 'fluent', 'persuasive', 'expressive'],
                'antonyms' => ['inarticulate', 'tongue-tied', 'unclear', 'mumbling'],
                'priority' => 84
            ],
            [
                'word' => 'Pragmatic',
                'meaning' => 'Dealing with things sensibly and realistically in a practical way',
                'example_sentence' => 'Her pragmatic approach to problem-solving saved the company money.',
                'pronunciation' => 'præɡˈmætɪk',
                'difficulty_level' => 'advanced',
                'word_type' => 'adjective',
                'synonyms' => ['practical', 'realistic', 'sensible', 'down-to-earth'],
                'antonyms' => ['idealistic', 'impractical', 'unrealistic', 'theoretical'],
                'priority' => 83
            ],
            [
                'word' => 'Versatile',
                'meaning' => 'Able to adapt or be adapted to many different functions or activities',
                'example_sentence' => 'She is a versatile athlete who excels in multiple sports.',
                'pronunciation' => 'ˈvɜːsətaɪl',
                'difficulty_level' => 'intermediate',
                'word_type' => 'adjective',
                'synonyms' => ['adaptable', 'flexible', 'multi-talented', 'all-around'],
                'antonyms' => ['inflexible', 'rigid', 'specialized', 'limited'],
                'priority' => 82
            ],
            [
                'word' => 'Collaborate',
                'meaning' => 'Work jointly on an activity, especially to produce something',
                'example_sentence' => 'Scientists from different countries collaborate on climate research.',
                'pronunciation' => 'kəˈlæbəreɪt',
                'difficulty_level' => 'intermediate',
                'word_type' => 'verb',
                'synonyms' => ['cooperate', 'work together', 'team up', 'partner'],
                'antonyms' => ['compete', 'oppose', 'work alone', 'conflict'],
                'priority' => 79
            ]
        ];

        // Create the specific words
        foreach ($specificWords as $wordData) {
            VocabularyWord::create($wordData);
        }

        $this->command->info('Created ' . count($specificWords) . ' specific vocabulary words.');

        // Create additional random words using factory
        $this->command->info('Creating additional vocabulary words using factory...');

        // Create 15 beginner words
        VocabularyWord::factory()->beginner()->count(15)->create();
        
        // Create 20 intermediate words  
        VocabularyWord::factory()->intermediate()->count(20)->create();
        
        // Create 15 advanced words
        VocabularyWord::factory()->advanced()->count(15)->create();
        
        // Create 5 inactive words for testing
        VocabularyWord::factory()->inactive()->count(5)->create();
        
        // Create 5 high priority words
        VocabularyWord::factory()->highPriority()->count(5)->create();

        $totalWords = VocabularyWord::count();
        $this->command->info("Successfully created {$totalWords} vocabulary words!");
        
        // Show breakdown by difficulty
        $beginner = VocabularyWord::where('difficulty_level', 'beginner')->count();
        $intermediate = VocabularyWord::where('difficulty_level', 'intermediate')->count();
        $advanced = VocabularyWord::where('difficulty_level', 'advanced')->count();
        $active = VocabularyWord::where('is_active', true)->count();
        
        $this->command->info("Breakdown:");
        $this->command->info("- Beginner: {$beginner}");
        $this->command->info("- Intermediate: {$intermediate}");
        $this->command->info("- Advanced: {$advanced}");
        $this->command->info("- Active: {$active}");
        $this->command->info("- Inactive: " . ($totalWords - $active));
    }
}