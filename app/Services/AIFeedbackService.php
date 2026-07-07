<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIFeedbackService
{
    /**
     * Generate feedback for writing submissions
     */
    public function generateWritingFeedback(string $text, string $taskType = 'task1'): array
    {
        try {
            // Basic grammar and spelling analysis
            $grammarAnalysis = $this->analyzeGrammar($text);
            $spellingAnalysis = $this->analyzeSpelling($text);
            $structureAnalysis = $this->analyzeStructure($text, $taskType);
            $vocabularyAnalysis = $this->analyzeVocabulary($text);
            
            // Calculate overall score
            $overallScore = $this->calculateWritingScore([
                'grammar' => $grammarAnalysis['score'],
                'spelling' => $spellingAnalysis['score'],
                'structure' => $structureAnalysis['score'],
                'vocabulary' => $vocabularyAnalysis['score']
            ]);
            
            return [
                'overall_score' => $overallScore,
                'feedback' => [
                    'grammar' => $grammarAnalysis,
                    'spelling' => $spellingAnalysis,
                    'structure' => $structureAnalysis,
                    'vocabulary' => $vocabularyAnalysis
                ],
                'suggestions' => $this->generateWritingSuggestions($text, $taskType),
                'strengths' => $this->identifyStrengths($text),
                'areas_for_improvement' => $this->identifyImprovements($text)
            ];
            
        } catch (Exception $e) {
            Log::error('AI Feedback Error: ' . $e->getMessage());
            
            return [
                'overall_score' => 0,
                'feedback' => [
                    'grammar' => ['score' => 0, 'issues' => [], 'comments' => 'Analysis unavailable'],
                    'spelling' => ['score' => 0, 'issues' => [], 'comments' => 'Analysis unavailable'],
                    'structure' => ['score' => 0, 'issues' => [], 'comments' => 'Analysis unavailable'],
                    'vocabulary' => ['score' => 0, 'issues' => [], 'comments' => 'Analysis unavailable']
                ],
                'suggestions' => ['Unable to generate suggestions at this time.'],
                'strengths' => [],
                'areas_for_improvement' => []
            ];
        }
    }

    /**
     * Generate feedback for speaking submissions
     */
    public function generateSpeakingFeedback(string $audioPath, string $transcription = null): array
    {
        try {
            // If no transcription provided, we'd normally use speech-to-text
            if (!$transcription) {
                $transcription = $this->transcribeAudio($audioPath);
            }
            
            $fluencyAnalysis = $this->analyzeFluency($transcription);
            $pronunciationAnalysis = $this->analyzePronunciation($audioPath);
            $vocabularyAnalysis = $this->analyzeVocabulary($transcription);
            $coherenceAnalysis = $this->analyzeCoherence($transcription);
            
            $overallScore = $this->calculateSpeakingScore([
                'fluency' => $fluencyAnalysis['score'],
                'pronunciation' => $pronunciationAnalysis['score'],
                'vocabulary' => $vocabularyAnalysis['score'],
                'coherence' => $coherenceAnalysis['score']
            ]);
            
            return [
                'overall_score' => $overallScore,
                'transcription' => $transcription,
                'feedback' => [
                    'fluency' => $fluencyAnalysis,
                    'pronunciation' => $pronunciationAnalysis,
                    'vocabulary' => $vocabularyAnalysis,
                    'coherence' => $coherenceAnalysis
                ],
                'suggestions' => $this->generateSpeakingSuggestions($transcription),
                'strengths' => $this->identifyStrengths($transcription),
                'areas_for_improvement' => $this->identifyImprovements($transcription)
            ];
            
        } catch (Exception $e) {
            Log::error('AI Speaking Feedback Error: ' . $e->getMessage());
            
            return [
                'overall_score' => 0,
                'transcription' => 'Transcription unavailable',
                'feedback' => [
                    'fluency' => ['score' => 0, 'comments' => 'Analysis unavailable'],
                    'pronunciation' => ['score' => 0, 'comments' => 'Analysis unavailable'],
                    'vocabulary' => ['score' => 0, 'comments' => 'Analysis unavailable'],
                    'coherence' => ['score' => 0, 'comments' => 'Analysis unavailable']
                ],
                'suggestions' => ['Unable to generate suggestions at this time.'],
                'strengths' => [],
                'areas_for_improvement' => []
            ];
        }
    }

    /**
     * Analyze grammar in text
     */
    private function analyzeGrammar(string $text): array
    {
        $issues = [];
        $score = 85; // Base score
        
        // Basic grammar checks
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            // Check for basic issues
            if (!preg_match('/^[A-Z]/', $sentence)) {
                $issues[] = "Sentence should start with a capital letter: '{$sentence}'";
                $score -= 2;
            }
            
            // Check for common grammar patterns
            if (preg_match('/\b(there|their|they\'re)\b/i', $sentence)) {
                // This would be more sophisticated in a real implementation
            }
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'comments' => count($issues) === 0 ? 'Good grammar usage overall.' : 'Some grammar issues detected.'
        ];
    }

    /**
     * Analyze spelling in text
     */
    private function analyzeSpelling(string $text): array
    {
        $issues = [];
        $score = 90; // Base score
        
        // Basic spelling checks (in a real implementation, you'd use a proper spell checker)
        $commonMisspellings = [
            'recieve' => 'receive',
            'seperate' => 'separate',
            'definately' => 'definitely',
            'occured' => 'occurred',
            'begining' => 'beginning'
        ];
        
        foreach ($commonMisspellings as $wrong => $correct) {
            if (stripos($text, $wrong) !== false) {
                $issues[] = "Possible misspelling: '{$wrong}' should be '{$correct}'";
                $score -= 5;
            }
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'issues' => $issues,
            'comments' => count($issues) === 0 ? 'No obvious spelling errors detected.' : 'Some spelling issues found.'
        ];
    }

    /**
     * Analyze text structure
     */
    private function analyzeStructure(string $text, string $taskType): array
    {
        $score = 75; // Base score
        $issues = [];
        
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        $paragraphCount = count($paragraphs);
        $wordCount = str_word_count($text);
        
        // Check word count
        $minWords = $taskType === 'task1' ? 150 : 250;
        if ($wordCount < $minWords) {
            $issues[] = "Word count ({$wordCount}) is below the minimum requirement ({$minWords} words)";
            $score -= 15;
        }
        
        // Check paragraph structure
        if ($paragraphCount < 3) {
            $issues[] = "Consider organizing your response into more paragraphs for better structure";
            $score -= 10;
        }
        
        // Check for introduction and conclusion
        $firstParagraph = $paragraphs[0] ?? '';
        $lastParagraph = end($paragraphs) ?: '';
        
        if (strlen($firstParagraph) < 50) {
            $issues[] = "Introduction could be more developed";
            $score -= 5;
        }
        
        if (strlen($lastParagraph) < 30) {
            $issues[] = "Conclusion could be stronger";
            $score -= 5;
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'word_count' => $wordCount,
            'paragraph_count' => $paragraphCount,
            'issues' => $issues,
            'comments' => count($issues) === 0 ? 'Good overall structure.' : 'Structure could be improved.'
        ];
    }

    /**
     * Analyze vocabulary usage
     */
    private function analyzeVocabulary(string $text): array
    {
        $words = str_word_count(strtolower($text), 1);
        $uniqueWords = array_unique($words);
        $vocabularyRichness = count($uniqueWords) / count($words);
        
        $score = min(100, $vocabularyRichness * 150); // Scale to 0-100
        
        // Check for advanced vocabulary
        $advancedWords = ['furthermore', 'consequently', 'nevertheless', 'substantial', 'comprehensive'];
        $advancedCount = 0;
        
        foreach ($advancedWords as $word) {
            if (in_array($word, $words)) {
                $advancedCount++;
            }
        }
        
        if ($advancedCount > 0) {
            $score += $advancedCount * 5;
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'vocabulary_richness' => round($vocabularyRichness, 3),
            'unique_words' => count($uniqueWords),
            'total_words' => count($words),
            'advanced_words_used' => $advancedCount,
            'comments' => $vocabularyRichness > 0.6 ? 'Good vocabulary variety.' : 'Try to use more varied vocabulary.'
        ];
    }

    /**
     * Analyze fluency (for speaking)
     */
    private function analyzeFluency(string $transcription): array
    {
        $words = str_word_count($transcription);
        $sentences = preg_split('/[.!?]+/', $transcription, -1, PREG_SPLIT_NO_EMPTY);
        
        $avgWordsPerSentence = $words / max(1, count($sentences));
        $score = min(100, $avgWordsPerSentence * 8); // Rough scoring
        
        return [
            'score' => max(0, min(100, $score)),
            'words_per_sentence' => round($avgWordsPerSentence, 1),
            'comments' => $score > 70 ? 'Good fluency demonstrated.' : 'Work on speaking more fluently.'
        ];
    }

    /**
     * Analyze pronunciation (placeholder - would need actual audio analysis)
     */
    private function analyzePronunciation(string $audioPath): array
    {
        // This would require actual audio processing in a real implementation
        return [
            'score' => 75, // Placeholder score
            'comments' => 'Pronunciation analysis requires advanced audio processing.'
        ];
    }

    /**
     * Analyze coherence
     */
    private function analyzeCoherence(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $score = 70; // Base score
        
        // Check for linking words
        $linkingWords = ['however', 'therefore', 'furthermore', 'moreover', 'consequently', 'additionally'];
        $linkingWordsFound = 0;
        
        foreach ($linkingWords as $word) {
            if (stripos($text, $word) !== false) {
                $linkingWordsFound++;
            }
        }
        
        $score += $linkingWordsFound * 5;
        
        return [
            'score' => max(0, min(100, $score)),
            'linking_words_used' => $linkingWordsFound,
            'comments' => $linkingWordsFound > 2 ? 'Good use of linking words.' : 'Try using more linking words for better coherence.'
        ];
    }

    /**
     * Transcribe audio (placeholder)
     */
    private function transcribeAudio(string $audioPath): string
    {
        // This would integrate with a speech-to-text service
        return "Audio transcription would be generated here using a speech-to-text service.";
    }

    /**
     * Calculate overall writing score
     */
    private function calculateWritingScore(array $scores): int
    {
        $weights = [
            'grammar' => 0.3,
            'spelling' => 0.2,
            'structure' => 0.3,
            'vocabulary' => 0.2
        ];
        
        $weightedSum = 0;
        foreach ($scores as $category => $score) {
            $weightedSum += $score * ($weights[$category] ?? 0.25);
        }
        
        return round($weightedSum);
    }

    /**
     * Calculate overall speaking score
     */
    private function calculateSpeakingScore(array $scores): int
    {
        $weights = [
            'fluency' => 0.25,
            'pronunciation' => 0.25,
            'vocabulary' => 0.25,
            'coherence' => 0.25
        ];
        
        $weightedSum = 0;
        foreach ($scores as $category => $score) {
            $weightedSum += $score * ($weights[$category] ?? 0.25);
        }
        
        return round($weightedSum);
    }

    /**
     * Generate writing suggestions
     */
    private function generateWritingSuggestions(string $text, string $taskType): array
    {
        $suggestions = [];
        
        $wordCount = str_word_count($text);
        $minWords = $taskType === 'task1' ? 150 : 250;
        
        if ($wordCount < $minWords) {
            $suggestions[] = "Expand your response to meet the minimum word requirement ({$minWords} words).";
        }
        
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        if (count($paragraphs) < 3) {
            $suggestions[] = "Organize your response into clear paragraphs with introduction, body, and conclusion.";
        }
        
        if ($taskType === 'task1') {
            $suggestions[] = "Ensure you describe all key features of the visual data.";
            $suggestions[] = "Use appropriate vocabulary for describing trends and comparisons.";
        } else {
            $suggestions[] = "Make sure to address all parts of the question.";
            $suggestions[] = "Support your arguments with relevant examples.";
        }
        
        return $suggestions;
    }

    /**
     * Generate speaking suggestions
     */
    private function generateSpeakingSuggestions(string $transcription): array
    {
        return [
            "Practice speaking at a steady pace with natural pauses.",
            "Use a variety of vocabulary and sentence structures.",
            "Organize your thoughts clearly with introduction, main points, and conclusion.",
            "Practice pronunciation of challenging words."
        ];
    }

    /**
     * Identify strengths in the text
     */
    private function identifyStrengths(string $text): array
    {
        $strengths = [];
        
        if (str_word_count($text) > 200) {
            $strengths[] = "Good length and development of ideas";
        }
        
        if (preg_match_all('/[.!?]/', $text) > 5) {
            $strengths[] = "Good use of varied sentence structures";
        }
        
        $advancedWords = ['furthermore', 'consequently', 'nevertheless', 'substantial', 'comprehensive'];
        foreach ($advancedWords as $word) {
            if (stripos($text, $word) !== false) {
                $strengths[] = "Use of advanced vocabulary";
                break;
            }
        }
        
        return $strengths;
    }

    /**
     * Identify areas for improvement
     */
    private function identifyImprovements(string $text): array
    {
        $improvements = [];
        
        if (str_word_count($text) < 150) {
            $improvements[] = "Develop ideas more fully with additional details and examples";
        }
        
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        if (count($paragraphs) < 3) {
            $improvements[] = "Improve organization with clearer paragraph structure";
        }
        
        $linkingWords = ['however', 'therefore', 'furthermore', 'moreover'];
        $hasLinking = false;
        foreach ($linkingWords as $word) {
            if (stripos($text, $word) !== false) {
                $hasLinking = true;
                break;
            }
        }
        
        if (!$hasLinking) {
            $improvements[] = "Use more linking words to improve coherence";
        }
        
        return $improvements;
    }
}