<?php

namespace App\Services;

use Illuminate\Support\Str;

class EnhancedEvaluationService
{
    private array $synonymDatabase;
    private array $commonVariations;

    public function __construct()
    {
        $this->initializeSynonymDatabase();
        $this->initializeCommonVariations();
    }

    /**
     * Enhanced Reading evaluation with fuzzy matching
     */
    public function evaluateReading(array $userAnswers, array $questions): array
    {
        $totalQuestions = count($questions);
        $correctAnswers = 0;
        $detailedResults = [];

        foreach ($userAnswers as $index => $userAnswer) {
            if (!isset($questions[$index])) continue;

            $question = $questions[$index];
            $isCorrect = $this->evaluateReadingAnswer($userAnswer, $question);
            
            if ($isCorrect) {
                $correctAnswers++;
            }

            $detailedResults[] = [
                'question_id' => $question['id'] ?? $index,
                'user_answer' => $userAnswer,
                'correct_answers' => $question['correct_answers'] ?? [],
                'is_correct' => $isCorrect,
                'question_type' => $question['type'] ?? 'unknown',
                'explanation' => $this->getExplanation($userAnswer, $question, $isCorrect)
            ];
        }

        $bandScore = $this->calculateReadingBandScore($correctAnswers, $totalQuestions);

        return [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy_percentage' => round(($correctAnswers / $totalQuestions) * 100, 2),
            'band_score' => $bandScore,
            'detailed_results' => $detailedResults
        ];
    }

    /**
     * Enhanced Listening evaluation with variation handling
     */
    public function evaluateListening(array $userAnswers, array $questions): array
    {
        $totalQuestions = count($questions);
        $correctAnswers = 0;
        $detailedResults = [];

        foreach ($userAnswers as $index => $userAnswer) {
            if (!isset($questions[$index])) continue;

            $question = $questions[$index];
            $isCorrect = $this->evaluateListeningAnswer($userAnswer, $question);
            
            if ($isCorrect) {
                $correctAnswers++;
            }

            $detailedResults[] = [
                'question_id' => $question['id'] ?? $index,
                'user_answer' => $userAnswer,
                'correct_answers' => $question['correct_answers'] ?? [],
                'is_correct' => $isCorrect,
                'question_type' => $question['type'] ?? 'unknown',
                'explanation' => $this->getExplanation($userAnswer, $question, $isCorrect)
            ];
        }

        $bandScore = $this->calculateListeningBandScore($correctAnswers, $totalQuestions);

        return [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy_percentage' => round(($correctAnswers / $totalQuestions) * 100, 2),
            'band_score' => $bandScore,
            'detailed_results' => $detailedResults
        ];
    }

    /**
     * Evaluate individual reading answer
     */
    private function evaluateReadingAnswer($userAnswer, array $question): bool
    {
        $correctAnswers = $question['correct_answers'] ?? [];
        $questionType = $question['type'] ?? 'multiple_choice';

        if (empty($correctAnswers)) {
            return false;
        }

        switch ($questionType) {
            case 'multiple_choice':
                return $this->evaluateMultipleChoice($userAnswer, $correctAnswers);
            
            case 'fill_blank':
            case 'short_answer':
                return $this->evaluateFillBlank($userAnswer, $correctAnswers);
            
            case 'true_false_not_given':
                return $this->evaluateTrueFalseNotGiven($userAnswer, $correctAnswers);
            
            case 'matching':
                return $this->evaluateMatching($userAnswer, $correctAnswers);
            
            case 'sentence_completion':
                return $this->evaluateSentenceCompletion($userAnswer, $correctAnswers);
            
            default:
                return $this->fuzzyMatch($userAnswer, $correctAnswers);
        }
    }

    /**
     * Evaluate individual listening answer
     */
    private function evaluateListeningAnswer($userAnswer, array $question): bool
    {
        $correctAnswers = $question['correct_answers'] ?? [];
        $questionType = $question['type'] ?? 'multiple_choice';

        if (empty($correctAnswers)) {
            return false;
        }

        switch ($questionType) {
            case 'form_completion':
            case 'note_completion':
                return $this->evaluateFormCompletion($userAnswer, $correctAnswers);
            
            case 'multiple_choice':
                return $this->evaluateMultipleChoice($userAnswer, $correctAnswers);
            
            case 'map_labeling':
            case 'diagram_labeling':
                return $this->evaluateLabeling($userAnswer, $correctAnswers);
            
            case 'table_completion':
                return $this->evaluateTableCompletion($userAnswer, $correctAnswers);
            
            default:
                return $this->fuzzyMatchListening($userAnswer, $correctAnswers);
        }
    }

    /**
     * Multiple choice evaluation (exact match)
     */
    private function evaluateMultipleChoice($userAnswer, array $correctAnswers): bool
    {
        return in_array($userAnswer, $correctAnswers, true);
    }

    /**
     * Fill in the blank with enhanced matching
     */
    private function evaluateFillBlank($userAnswer, array $correctAnswers): bool
    {
        $userAnswer = $this->normalizeAnswer($userAnswer);
        
        foreach ($correctAnswers as $correct) {
            $correct = $this->normalizeAnswer($correct);
            
            // Exact match
            if ($userAnswer === $correct) {
                return true;
            }
            
            // Fuzzy match with spelling tolerance
            if ($this->fuzzyMatch($userAnswer, [$correct])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * True/False/Not Given evaluation
     */
    private function evaluateTrueFalseNotGiven($userAnswer, array $correctAnswers): bool
    {
        $userAnswer = strtolower(trim($userAnswer));
        $correctAnswer = strtolower(trim($correctAnswers[0] ?? ''));
        
        // Handle variations
        $variations = [
            'true' => ['true', 't', 'yes', 'correct'],
            'false' => ['false', 'f', 'no', 'incorrect'],
            'not given' => ['not given', 'ng', 'not mentioned', 'no information']
        ];
        
        foreach ($variations as $standard => $variants) {
            if (in_array($userAnswer, $variants) && in_array($correctAnswer, $variants)) {
                return true;
            }
        }
        
        return $userAnswer === $correctAnswer;
    }

    /**
     * Matching evaluation
     */
    private function evaluateMatching($userAnswer, array $correctAnswers): bool
    {
        return in_array($userAnswer, $correctAnswers, true);
    }

    /**
     * Sentence completion evaluation
     */
    private function evaluateSentenceCompletion($userAnswer, array $correctAnswers): bool
    {
        return $this->evaluateFillBlank($userAnswer, $correctAnswers);
    }

    /**
     * Form completion for listening
     */
    private function evaluateFormCompletion($userAnswer, array $correctAnswers): bool
    {
        $userAnswer = $this->normalizeListeningAnswer($userAnswer);
        
        foreach ($correctAnswers as $correct) {
            $correct = $this->normalizeListeningAnswer($correct);
            
            // Exact match
            if ($userAnswer === $correct) {
                return true;
            }
            
            // Handle common listening variations
            if ($this->matchListeningVariations($userAnswer, $correct)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Labeling evaluation (maps, diagrams)
     */
    private function evaluateLabeling($userAnswer, array $correctAnswers): bool
    {
        return $this->evaluateFormCompletion($userAnswer, $correctAnswers);
    }

    /**
     * Table completion evaluation
     */
    private function evaluateTableCompletion($userAnswer, array $correctAnswers): bool
    {
        return $this->evaluateFormCompletion($userAnswer, $correctAnswers);
    }

    /**
     * Fuzzy matching for reading answers
     */
    private function fuzzyMatch($userAnswer, array $correctAnswers): bool
    {
        $userAnswer = $this->normalizeAnswer($userAnswer);
        
        foreach ($correctAnswers as $correct) {
            $correct = $this->normalizeAnswer($correct);
            
            // Exact match
            if ($userAnswer === $correct) {
                return true;
            }
            
            // Levenshtein distance (spelling tolerance)
            if ($this->calculateSimilarity($userAnswer, $correct) >= 0.8) {
                return true;
            }
            
            // Synonym matching
            if ($this->areSynonyms($userAnswer, $correct)) {
                return true;
            }
            
            // Plural/singular variations
            if ($this->arePluralizationVariants($userAnswer, $correct)) {
                return true;
            }
            
            // Partial word matching for compound words
            if ($this->isPartialMatch($userAnswer, $correct)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Fuzzy matching for listening answers
     */
    private function fuzzyMatchListening($userAnswer, array $correctAnswers): bool
    {
        $userAnswer = $this->normalizeListeningAnswer($userAnswer);
        
        foreach ($correctAnswers as $correct) {
            $correct = $this->normalizeListeningAnswer($correct);
            
            // Exact match
            if ($userAnswer === $correct) {
                return true;
            }
            
            // Handle listening-specific variations
            if ($this->matchListeningVariations($userAnswer, $correct)) {
                return true;
            }
            
            // Spelling tolerance (more lenient for listening)
            if ($this->calculateSimilarity($userAnswer, $correct) >= 0.75) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Normalize answer for reading
     */
    private function normalizeAnswer(string $answer): string
    {
        $answer = trim($answer);
        $answer = strtolower($answer);
        $answer = preg_replace('/[^\w\s]/', '', $answer); // Remove punctuation
        $answer = preg_replace('/\s+/', ' ', $answer); // Normalize spaces
        return $answer;
    }

    /**
     * Normalize answer for listening (more lenient)
     */
    private function normalizeListeningAnswer(string $answer): string
    {
        $answer = trim($answer);
        $answer = strtolower($answer);
        
        // Handle common listening variations
        $answer = str_replace(['$', '£', '€', '%'], '', $answer);
        $answer = preg_replace('/[^\w\s\d]/', '', $answer);
        $answer = preg_replace('/\s+/', ' ', $answer);
        
        return $answer;
    }

    /**
     * Calculate similarity between two strings
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 1.0;
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    /**
     * Check if two words are synonyms
     */
    private function areSynonyms(string $word1, string $word2): bool
    {
        foreach ($this->synonymDatabase as $synonymGroup) {
            if (in_array($word1, $synonymGroup) && in_array($word2, $synonymGroup)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if words are plural/singular variations
     */
    private function arePluralizationVariants(string $word1, string $word2): bool
    {
        // Simple pluralization rules
        $singular1 = rtrim($word1, 's');
        $singular2 = rtrim($word2, 's');
        
        if ($singular1 === $singular2) return true;
        
        // Handle irregular plurals
        $irregularPlurals = [
            'child' => 'children',
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'foot' => 'feet',
            'tooth' => 'teeth'
        ];
        
        foreach ($irregularPlurals as $singular => $plural) {
            if (($word1 === $singular && $word2 === $plural) || 
                ($word1 === $plural && $word2 === $singular)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for partial word matching
     */
    private function isPartialMatch(string $word1, string $word2): bool
    {
        $minLength = 4; // Minimum length for partial matching
        
        if (strlen($word1) < $minLength || strlen($word2) < $minLength) {
            return false;
        }
        
        // Check if one word contains the other
        return strpos($word1, $word2) !== false || strpos($word2, $word1) !== false;
    }

    /**
     * Match listening-specific variations
     */
    private function matchListeningVariations(string $userAnswer, string $correct): bool
    {
        foreach ($this->commonVariations as $pattern => $variations) {
            if (in_array($userAnswer, $variations) && in_array($correct, $variations)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate Reading band score
     */
    private function calculateReadingBandScore(int $correct, int $total): float
    {
        if ($total === 0) return 0;
        
        $percentage = ($correct / $total) * 100;
        
        // IELTS Reading band score conversion
        if ($percentage >= 95) return 9.0;
        if ($percentage >= 89) return 8.5;
        if ($percentage >= 83) return 8.0;
        if ($percentage >= 75) return 7.5;
        if ($percentage >= 67) return 7.0;
        if ($percentage >= 58) return 6.5;
        if ($percentage >= 50) return 6.0;
        if ($percentage >= 42) return 5.5;
        if ($percentage >= 33) return 5.0;
        if ($percentage >= 25) return 4.5;
        if ($percentage >= 17) return 4.0;
        if ($percentage >= 8) return 3.5;
        if ($percentage >= 4) return 3.0;
        
        return 2.5;
    }

    /**
     * Calculate Listening band score
     */
    private function calculateListeningBandScore(int $correct, int $total): float
    {
        if ($total === 0) return 0;
        
        $percentage = ($correct / $total) * 100;
        
        // IELTS Listening band score conversion
        if ($percentage >= 97) return 9.0;
        if ($percentage >= 92) return 8.5;
        if ($percentage >= 87) return 8.0;
        if ($percentage >= 80) return 7.5;
        if ($percentage >= 72) return 7.0;
        if ($percentage >= 65) return 6.5;
        if ($percentage >= 57) return 6.0;
        if ($percentage >= 50) return 5.5;
        if ($percentage >= 42) return 5.0;
        if ($percentage >= 35) return 4.5;
        if ($percentage >= 27) return 4.0;
        if ($percentage >= 20) return 3.5;
        if ($percentage >= 12) return 3.0;
        
        return 2.5;
    }

    /**
     * Get explanation for answer
     */
    private function getExplanation($userAnswer, array $question, bool $isCorrect): string
    {
        if ($isCorrect) {
            return "Correct! Your answer matches the expected response.";
        }
        
        $correctAnswers = $question['correct_answers'] ?? [];
        $correctAnswersStr = implode(', ', $correctAnswers);
        
        return "Incorrect. The correct answer(s): {$correctAnswersStr}. Your answer: {$userAnswer}";
    }

    /**
     * Initialize synonym database
     */
    private function initializeSynonymDatabase(): void
    {
        $this->synonymDatabase = [
            ['big', 'large', 'huge', 'enormous', 'massive', 'giant'],
            ['small', 'little', 'tiny', 'minute', 'miniature'],
            ['happy', 'glad', 'joyful', 'cheerful', 'delighted'],
            ['sad', 'unhappy', 'miserable', 'depressed', 'sorrowful'],
            ['fast', 'quick', 'rapid', 'swift', 'speedy'],
            ['slow', 'sluggish', 'gradual', 'leisurely'],
            ['smart', 'intelligent', 'clever', 'bright', 'brilliant'],
            ['stupid', 'dumb', 'foolish', 'ignorant'],
            ['beautiful', 'pretty', 'attractive', 'gorgeous', 'lovely'],
            ['ugly', 'hideous', 'unattractive', 'unsightly'],
            ['important', 'significant', 'crucial', 'vital', 'essential'],
            ['difficult', 'hard', 'challenging', 'tough', 'complex'],
            ['easy', 'simple', 'effortless', 'straightforward'],
            ['old', 'ancient', 'elderly', 'aged', 'mature'],
            ['new', 'recent', 'modern', 'contemporary', 'fresh'],
            ['good', 'excellent', 'great', 'wonderful', 'fantastic'],
            ['bad', 'terrible', 'awful', 'horrible', 'dreadful']
        ];
    }

    /**
     * Initialize common variations for listening
     */
    private function initializeCommonVariations(): void
    {
        $this->commonVariations = [
            'numbers' => ['1', 'one', 'first'],
            'dates' => ['jan', 'january', '01'],
            'times' => ['2:30', '2.30', 'half past two', 'two thirty'],
            'money' => ['$50', '50 dollars', 'fifty dollars'],
            'spelling' => ['center', 'centre', 'color', 'colour'],
            'contractions' => ["don't", 'do not', "can't", 'cannot', "won't", 'will not']
        ];
    }
}