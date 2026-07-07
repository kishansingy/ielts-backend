<?php

namespace Database\Factories;

use App\Models\VocabularyWord;
use Illuminate\Database\Eloquent\Factories\Factory;

class VocabularyWordFactory extends Factory
{
    protected $model = VocabularyWord::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $words = [
            [
                'word' => 'Abundant',
                'meaning' => 'Existing or available in large quantities; plentiful',
                'example_sentence' => 'The region has abundant natural resources that support its economy.',
                'pronunciation' => 'əˈbʌndənt',
                'word_type' => 'adjective',
                'synonyms' => ['plentiful', 'copious', 'ample', 'profuse'],
                'antonyms' => ['scarce', 'sparse', 'limited', 'insufficient']
            ],
            [
                'word' => 'Meticulous',
                'meaning' => 'Showing great attention to detail; very careful and precise',
                'example_sentence' => 'She was meticulous in her research, checking every source twice.',
                'pronunciation' => 'mɪˈtɪkjʊləs',
                'word_type' => 'adjective',
                'synonyms' => ['careful', 'thorough', 'precise', 'scrupulous'],
                'antonyms' => ['careless', 'sloppy', 'hasty', 'negligent']
            ],
            [
                'word' => 'Resilient',
                'meaning' => 'Able to withstand or recover quickly from difficult conditions',
                'example_sentence' => 'The resilient community rebuilt their town after the natural disaster.',
                'pronunciation' => 'rɪˈzɪliənt',
                'word_type' => 'adjective',
                'synonyms' => ['tough', 'strong', 'flexible', 'adaptable'],
                'antonyms' => ['fragile', 'weak', 'brittle', 'vulnerable']
            ],
            [
                'word' => 'Innovative',
                'meaning' => 'Featuring new methods; advanced and original',
                'example_sentence' => 'The company\'s innovative approach to marketing attracted many customers.',
                'pronunciation' => 'ˈɪnəveɪtɪv',
                'word_type' => 'adjective',
                'synonyms' => ['creative', 'original', 'inventive', 'pioneering'],
                'antonyms' => ['traditional', 'conventional', 'outdated', 'conservative']
            ],
            [
                'word' => 'Comprehensive',
                'meaning' => 'Complete and including everything that is necessary',
                'example_sentence' => 'The comprehensive report covered all aspects of the environmental impact.',
                'pronunciation' => 'ˌkɒmprɪˈhensɪv',
                'word_type' => 'adjective',
                'synonyms' => ['complete', 'thorough', 'extensive', 'all-inclusive'],
                'antonyms' => ['incomplete', 'partial', 'limited', 'superficial']
            ],
            [
                'word' => 'Sustainable',
                'meaning' => 'Able to be maintained at a certain rate or level without depleting resources',
                'example_sentence' => 'The government promotes sustainable development to protect the environment.',
                'pronunciation' => 'səˈsteɪnəbl',
                'word_type' => 'adjective',
                'synonyms' => ['maintainable', 'viable', 'renewable', 'eco-friendly'],
                'antonyms' => ['unsustainable', 'depleting', 'wasteful', 'harmful']
            ],
            [
                'word' => 'Eloquent',
                'meaning' => 'Fluent or persuasive in speaking or writing',
                'example_sentence' => 'The politician gave an eloquent speech that moved the audience.',
                'pronunciation' => 'ˈeləkwənt',
                'word_type' => 'adjective',
                'synonyms' => ['articulate', 'fluent', 'persuasive', 'expressive'],
                'antonyms' => ['inarticulate', 'tongue-tied', 'unclear', 'mumbling']
            ],
            [
                'word' => 'Pragmatic',
                'meaning' => 'Dealing with things sensibly and realistically in a practical way',
                'example_sentence' => 'Her pragmatic approach to problem-solving saved the company money.',
                'pronunciation' => 'præɡˈmætɪk',
                'word_type' => 'adjective',
                'synonyms' => ['practical', 'realistic', 'sensible', 'down-to-earth'],
                'antonyms' => ['idealistic', 'impractical', 'unrealistic', 'theoretical']
            ],
            [
                'word' => 'Versatile',
                'meaning' => 'Able to adapt or be adapted to many different functions or activities',
                'example_sentence' => 'She is a versatile athlete who excels in multiple sports.',
                'pronunciation' => 'ˈvɜːsətaɪl',
                'word_type' => 'adjective',
                'synonyms' => ['adaptable', 'flexible', 'multi-talented', 'all-around'],
                'antonyms' => ['inflexible', 'rigid', 'specialized', 'limited']
            ],
            [
                'word' => 'Profound',
                'meaning' => 'Very great or intense; having deep insight or understanding',
                'example_sentence' => 'The book had a profound impact on how I view the world.',
                'pronunciation' => 'prəˈfaʊnd',
                'word_type' => 'adjective',
                'synonyms' => ['deep', 'intense', 'significant', 'meaningful'],
                'antonyms' => ['shallow', 'superficial', 'trivial', 'insignificant']
            ],
            [
                'word' => 'Collaborate',
                'meaning' => 'Work jointly on an activity, especially to produce something',
                'example_sentence' => 'Scientists from different countries collaborate on climate research.',
                'pronunciation' => 'kəˈlæbəreɪt',
                'word_type' => 'verb',
                'synonyms' => ['cooperate', 'work together', 'team up', 'partner'],
                'antonyms' => ['compete', 'oppose', 'work alone', 'conflict']
            ],
            [
                'word' => 'Analyze',
                'meaning' => 'Examine methodically and in detail the constitution or structure of something',
                'example_sentence' => 'We need to analyze the data before making any conclusions.',
                'pronunciation' => 'ˈænəlaɪz',
                'word_type' => 'verb',
                'synonyms' => ['examine', 'study', 'investigate', 'scrutinize'],
                'antonyms' => ['ignore', 'overlook', 'synthesize', 'combine']
            ],
            [
                'word' => 'Implement',
                'meaning' => 'Put a decision or plan into effect; carry out',
                'example_sentence' => 'The company will implement the new policy next month.',
                'pronunciation' => 'ˈɪmplɪment',
                'word_type' => 'verb',
                'synonyms' => ['execute', 'carry out', 'put into practice', 'apply'],
                'antonyms' => ['abandon', 'cancel', 'ignore', 'neglect']
            ],
            [
                'word' => 'Enhance',
                'meaning' => 'Intensify, increase, or further improve the quality, value, or extent of something',
                'example_sentence' => 'The new software will enhance our productivity significantly.',
                'pronunciation' => 'ɪnˈhæns',
                'word_type' => 'verb',
                'synonyms' => ['improve', 'boost', 'strengthen', 'amplify'],
                'antonyms' => ['diminish', 'reduce', 'weaken', 'impair']
            ],
            [
                'word' => 'Phenomenon',
                'meaning' => 'A fact or situation that is observed to exist or happen',
                'example_sentence' => 'Climate change is a global phenomenon that affects everyone.',
                'pronunciation' => 'fɪˈnɒmɪnən',
                'word_type' => 'noun',
                'synonyms' => ['occurrence', 'event', 'happening', 'manifestation'],
                'antonyms' => ['normality', 'regularity', 'commonplace', 'ordinary']
            ],
            [
                'word' => 'Hypothesis',
                'meaning' => 'A supposition or proposed explanation made on the basis of limited evidence',
                'example_sentence' => 'The scientist tested her hypothesis through careful experimentation.',
                'pronunciation' => 'haɪˈpɒθɪsɪs',
                'word_type' => 'noun',
                'synonyms' => ['theory', 'assumption', 'supposition', 'conjecture'],
                'antonyms' => ['fact', 'certainty', 'proof', 'evidence']
            ],
            [
                'word' => 'Methodology',
                'meaning' => 'A system of methods used in a particular area of study or activity',
                'example_sentence' => 'The research methodology was carefully designed to ensure accurate results.',
                'pronunciation' => 'ˌmeθəˈdɒlədʒi',
                'word_type' => 'noun',
                'synonyms' => ['approach', 'system', 'procedure', 'technique'],
                'antonyms' => ['randomness', 'chaos', 'disorder', 'improvisation']
            ],
            [
                'word' => 'Infrastructure',
                'meaning' => 'The basic physical and organizational structures needed for operation',
                'example_sentence' => 'The country invested heavily in improving its digital infrastructure.',
                'pronunciation' => 'ˈɪnfrəstrʌktʃə',
                'word_type' => 'noun',
                'synonyms' => ['framework', 'foundation', 'structure', 'system'],
                'antonyms' => ['superstructure', 'surface', 'exterior', 'facade']
            ],
            [
                'word' => 'Paradigm',
                'meaning' => 'A typical example or pattern of something; a model',
                'example_sentence' => 'The new teaching paradigm focuses on student-centered learning.',
                'pronunciation' => 'ˈpærədaɪm',
                'word_type' => 'noun',
                'synonyms' => ['model', 'pattern', 'framework', 'template'],
                'antonyms' => ['deviation', 'exception', 'anomaly', 'irregularity']
            ],
            [
                'word' => 'Synthesis',
                'meaning' => 'The combination of ideas to form a theory or system',
                'example_sentence' => 'The report provided a synthesis of all the research findings.',
                'pronunciation' => 'ˈsɪnθɪsɪs',
                'word_type' => 'noun',
                'synonyms' => ['combination', 'integration', 'fusion', 'amalgamation'],
                'antonyms' => ['analysis', 'separation', 'division', 'breakdown']
            ]
        ];

        $wordData = $this->faker->randomElement($words);
        
        return [
            'word' => $wordData['word'],
            'meaning' => $wordData['meaning'],
            'example_sentence' => $wordData['example_sentence'],
            'pronunciation' => $wordData['pronunciation'] ?? null,
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'word_type' => $wordData['word_type'] ?? $this->faker->randomElement(['noun', 'verb', 'adjective', 'adverb']),
            'oxford_url' => null, // Will be auto-generated
            'synonyms' => $wordData['synonyms'] ?? [],
            'antonyms' => $wordData['antonyms'] ?? [],
            'priority' => $this->faker->numberBetween(50, 100),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Create a beginner level word
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'beginner',
            'priority' => $this->faker->numberBetween(70, 85),
        ]);
    }

    /**
     * Create an intermediate level word
     */
    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'intermediate',
            'priority' => $this->faker->numberBetween(80, 95),
        ]);
    }

    /**
     * Create an advanced level word
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'advanced',
            'priority' => $this->faker->numberBetween(85, 100),
        ]);
    }

    /**
     * Create an inactive word
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a high priority word
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $this->faker->numberBetween(90, 100),
        ]);
    }
}