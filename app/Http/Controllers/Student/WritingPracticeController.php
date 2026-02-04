<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\WritingTask;
use App\Models\Submission;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WritingPracticeController extends Controller
{
    /**
     * Get available writing tasks for practice
     */
    public function index(Request $request)
    {
        $query = WritingTask::query();
        
        // Filter by task type if provided
        if ($request->has('task_type')) {
            $query->byType($request->task_type);
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->get();
        
        // Add user's submission count for each task
        $userId = Auth::id();
        $tasks->each(function ($task) use ($userId) {
            $task->user_submissions_count = $task->submissions()
                ->where('user_id', $userId)
                ->count();
                
            $task->latest_submission = $task->submissions()
                ->where('user_id', $userId)
                ->latest('submitted_at')
                ->first();
                
            // Add review data if submissions exist
            if ($task->user_submissions_count > 0) {
                $task->can_review = true;
                $task->latest_submission_date = $task->latest_submission?->submitted_at;
                $task->latest_score = $task->latest_submission?->score;
            }
        });
        
        return response()->json($tasks);
    }

    /**
     * Get a specific writing task for practice
     */
    public function show($taskId)
    {
        $task = WritingTask::findOrFail($taskId);
        
        return response()->json($task);
    }

    /**
     * Submit a writing response
     */
    public function submit(Request $request, $taskId)
    {
        $task = WritingTask::findOrFail($taskId);
        
        $request->validate([
            'content' => 'required|string|min:50',
            'time_spent' => 'required|integer|min:0',
        ]);

        // Check word count
        $wordCount = str_word_count(strip_tags($request->content));
        
        DB::beginTransaction();
        
        try {
            // Create submission
            $submission = Submission::create([
                'user_id' => Auth::id(),
                'task_id' => $task->id,
                'submission_type' => 'writing',
                'content' => $request->content,
                'submitted_at' => now(),
            ]);
            
            // Create attempt record for tracking
            $attempt = Attempt::create([
                'user_id' => Auth::id(),
                'module_type' => 'writing',
                'content_id' => $task->id,
                'content_type' => WritingTask::class,
                'score' => 0, // Will be updated when AI feedback is processed
                'max_score' => 100, // Standard writing score
                'time_spent' => $request->time_spent,
                'completed_at' => now(),
            ]);
            
            // TODO: Process AI feedback here
            $aiFeedback = $this->processAiFeedback($request->content, $task);
            
            if ($aiFeedback) {
                $submission->update([
                    'ai_feedback' => $aiFeedback,
                    'score' => $aiFeedback['overall_score'] ?? null,
                ]);
                
                $attempt->update([
                    'score' => $aiFeedback['overall_score'] ?? 0,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Writing submission successful',
                'submission' => $submission->fresh(),
                'word_count' => $wordCount,
                'word_limit' => $task->word_limit,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error submitting writing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's writing submission history
     */
    public function history()
    {
        $submissions = Submission::with(['task'])
            ->byUser(Auth::id())
            ->byType('writing')
            ->orderBy('submitted_at', 'desc')
            ->paginate(10);
            
        return response()->json($submissions);
    }

    /**
     * Get detailed results for a specific submission
     */
    public function results($submissionId)
    {
        $submission = Submission::with(['writingTask'])
            ->findOrFail($submissionId);
            
        // Verify this submission belongs to the authenticated user
        if ($submission->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Add detailed feedback and suggestions
        $detailedFeedback = $this->getDetailedWritingFeedback($submission);
        $improvementSuggestions = $this->getWritingImprovementSuggestions($submission);
        $bandAnalysis = $this->getBandAnalysis($submission);
        
        return response()->json([
            'submission' => $submission,
            'detailed_feedback' => $detailedFeedback,
            'improvement_suggestions' => $improvementSuggestions,
            'band_analysis' => $bandAnalysis
        ]);
    }

    /**
     * Get improvement tips for writing module
     */
    public function getImprovementTips(Request $request)
    {
        $userId = Auth::id();
        $bandLevel = $request->get('band_level', '7'); // Default to band 7
        
        // Get user's recent writing submissions
        $recentSubmissions = Submission::where('user_id', $userId)
            ->where('submission_type', 'writing')
            ->orderBy('submitted_at', 'desc')
            ->limit(5)
            ->get();

        $tips = $this->generateBandSpecificWritingTips($bandLevel, $recentSubmissions);
        
        return response()->json([
            'band_level' => $bandLevel,
            'tips' => $tips,
            'based_on_submissions' => $recentSubmissions->count(),
            'module' => 'writing'
        ]);
    }

    /**
     * Process AI feedback for writing submission
     * This is a placeholder for AI integration
     */
    private function processAiFeedback($content, $task)
    {
        // Basic analysis without external AI service
        $wordCount = str_word_count(strip_tags($content));
        $characterCount = strlen($content);
        $sentenceCount = preg_match_all('/[.!?]+/', $content);
        
        // Basic scoring based on word count and task requirements
        $wordCountScore = min(100, ($wordCount / $task->word_limit) * 100);
        $lengthScore = $wordCount >= ($task->word_limit * 0.8) ? 80 : 60;
        
        // Simple grammar check (count of common errors)
        $grammarScore = 75; // Placeholder
        
        $overallScore = ($wordCountScore + $lengthScore + $grammarScore) / 3;
        
        return [
            'overall_score' => round($overallScore, 2),
            'word_count' => $wordCount,
            'character_count' => $characterCount,
            'sentence_count' => $sentenceCount,
            'feedback' => [
                'strengths' => [
                    'Good use of vocabulary',
                    'Clear structure'
                ],
                'improvements' => [
                    'Consider varying sentence length',
                    'Check for grammatical accuracy'
                ],
                'suggestions' => [
                    'Use more connecting words',
                    'Provide more specific examples'
                ]
            ],
            'scores' => [
                'task_achievement' => round($lengthScore, 2),
                'coherence_cohesion' => 70,
                'lexical_resource' => 75,
                'grammatical_accuracy' => round($grammarScore, 2),
            ]
        ];
    }

    /**
     * Get detailed writing feedback
     */
    private function getDetailedWritingFeedback($submission)
    {
        $aiFeedback = $submission->ai_feedback ?? [];
        $wordCount = str_word_count(strip_tags($submission->content));
        $task = $submission->writingTask; // Use the specific relationship
        
        return [
            'word_analysis' => [
                'word_count' => $wordCount,
                'target_words' => $task->word_limit ?? 250,
                'meets_requirement' => $wordCount >= ($task->word_limit ?? 250) * 0.8
            ],
            'structure_analysis' => [
                'has_introduction' => $this->hasIntroduction($submission->content),
                'has_conclusion' => $this->hasConclusion($submission->content),
                'paragraph_count' => $this->countParagraphs($submission->content)
            ],
            'language_analysis' => [
                'vocabulary_variety' => $this->analyzeVocabularyVariety($submission->content),
                'sentence_variety' => $this->analyzeSentenceVariety($submission->content),
                'grammar_accuracy' => $aiFeedback['scores']['grammatical_accuracy'] ?? 70
            ],
            'task_response' => [
                'addresses_prompt' => true, // Placeholder
                'provides_examples' => $this->hasExamples($submission->content),
                'clear_position' => $this->hasClearPosition($submission->content)
            ]
        ];
    }

    /**
     * Get writing improvement suggestions
     */
    private function getWritingImprovementSuggestions($submission)
    {
        $wordCount = str_word_count(strip_tags($submission->content));
        $task = $submission->writingTask; // Use the specific relationship
        $targetWords = $task->word_limit ?? 250;
        
        $suggestions = [
            'immediate' => [],
            'short_term' => [],
            'long_term' => []
        ];

        // Word count suggestions
        if ($wordCount < $targetWords * 0.8) {
            $suggestions['immediate'][] = "Increase word count - you wrote {$wordCount} words but need at least " . ($targetWords * 0.8);
            $suggestions['immediate'][] = "Add more examples and explanations to support your points";
        } elseif ($wordCount > $targetWords * 1.2) {
            $suggestions['immediate'][] = "Reduce word count - you wrote {$wordCount} words but should aim for around {$targetWords}";
            $suggestions['immediate'][] = "Focus on being more concise and direct";
        }

        // Structure suggestions
        if (!$this->hasIntroduction($submission->content)) {
            $suggestions['immediate'][] = "Add a clear introduction that introduces your topic and main argument";
        }
        
        if (!$this->hasConclusion($submission->content)) {
            $suggestions['immediate'][] = "Include a conclusion that summarizes your main points";
        }

        // General improvement suggestions
        $suggestions['short_term'] = [
            'Practice writing within time limits (40 minutes for Task 2)',
            'Study model essays to understand good structure',
            'Build academic vocabulary for your topic areas',
            'Practice using linking words and phrases'
        ];

        $suggestions['long_term'] = [
            'Read academic articles to improve vocabulary and style',
            'Practice different essay types (opinion, discussion, problem-solution)',
            'Work on complex sentence structures',
            'Develop critical thinking skills for stronger arguments'
        ];

        return $suggestions;
    }

    /**
     * Get band analysis
     */
    private function getBandAnalysis($submission)
    {
        $aiFeedback = $submission->ai_feedback ?? [];
        $overallScore = $aiFeedback['overall_score'] ?? 65;
        
        // Convert percentage to band score
        $bandScore = $this->convertToBandScore($overallScore);
        
        return [
            'current_band' => $bandScore,
            'target_band' => min(9.0, $bandScore + 0.5),
            'band_breakdown' => [
                'task_achievement' => $this->convertToBandScore($aiFeedback['scores']['task_achievement'] ?? 70),
                'coherence_cohesion' => $this->convertToBandScore($aiFeedback['scores']['coherence_cohesion'] ?? 70),
                'lexical_resource' => $this->convertToBandScore($aiFeedback['scores']['lexical_resource'] ?? 75),
                'grammatical_accuracy' => $this->convertToBandScore($aiFeedback['scores']['grammatical_accuracy'] ?? 70)
            ],
            'next_level_requirements' => $this->getNextLevelRequirements($bandScore)
        ];
    }

    /**
     * Generate band-specific writing tips
     */
    private function generateBandSpecificWritingTips($bandLevel, $recentSubmissions)
    {
        $tips = [
            'general' => [],
            'task_achievement' => [],
            'coherence_cohesion' => [],
            'lexical_resource' => [],
            'grammatical_accuracy' => []
        ];

        switch ($bandLevel) {
            case '6':
                $tips['general'] = [
                    'Focus on addressing all parts of the task clearly',
                    'Organize your essay with clear paragraphs',
                    'Use simple linking words effectively',
                    'Aim for 250+ words with clear examples'
                ];
                $tips['task_achievement'] = [
                    'Make sure you answer the question directly',
                    'Provide relevant examples for each main point',
                    'State your opinion clearly if asked'
                ];
                $tips['coherence_cohesion'] = [
                    'Use basic linking words: however, therefore, for example',
                    'Start each paragraph with a clear topic sentence',
                    'Use pronouns to avoid repetition'
                ];
                break;

            case '7':
                $tips['general'] = [
                    'Develop ideas more fully with detailed explanations',
                    'Use a wider range of vocabulary accurately',
                    'Vary your sentence structures',
                    'Show clear progression of ideas'
                ];
                $tips['lexical_resource'] = [
                    'Use less common vocabulary appropriately',
                    'Show awareness of style and collocation',
                    'Avoid repetition by using synonyms'
                ];
                $tips['grammatical_accuracy'] = [
                    'Use complex sentences with subordinate clauses',
                    'Master conditional sentences',
                    'Use passive voice appropriately'
                ];
                break;

            case '8':
                $tips['general'] = [
                    'Present sophisticated ideas with nuanced arguments',
                    'Use precise vocabulary with natural collocations',
                    'Demonstrate flexible use of complex structures',
                    'Show subtle understanding of the topic'
                ];
                $tips['task_achievement'] = [
                    'Address all aspects with thorough development',
                    'Present clear, relevant, and extended examples',
                    'Show sophisticated understanding of the issue'
                ];
                break;

            case '9':
                $tips['general'] = [
                    'Demonstrate complete command of language',
                    'Use wide range of vocabulary with precision',
                    'Show full flexibility in sentence structures',
                    'Present highly sophisticated arguments'
                ];
                break;

            default:
                $tips['general'] = [
                    'Practice basic essay structure: introduction, body, conclusion',
                    'Focus on clear, simple sentences first',
                    'Build vocabulary gradually',
                    'Practice writing regularly'
                ];
        }

        return $tips;
    }

    // Helper methods for analysis
    private function hasIntroduction($content) {
        $sentences = explode('.', $content);
        return count($sentences) > 2; // Simplified check
    }

    private function hasConclusion($content) {
        $lowerContent = strtolower($content);
        return strpos($lowerContent, 'conclusion') !== false || 
               strpos($lowerContent, 'in summary') !== false ||
               strpos($lowerContent, 'to conclude') !== false;
    }

    private function countParagraphs($content) {
        return substr_count($content, "\n\n") + 1;
    }

    private function analyzeVocabularyVariety($content) {
        $words = str_word_count($content, 1);
        $uniqueWords = array_unique(array_map('strtolower', $words));
        return count($uniqueWords) / count($words) * 100; // Percentage of unique words
    }

    private function analyzeSentenceVariety($content) {
        $sentences = preg_split('/[.!?]+/', $content);
        $lengths = array_map('str_word_count', $sentences);
        return count(array_unique($lengths)) > 3; // Has variety if more than 3 different lengths
    }

    private function hasExamples($content) {
        $lowerContent = strtolower($content);
        return strpos($lowerContent, 'for example') !== false ||
               strpos($lowerContent, 'for instance') !== false ||
               strpos($lowerContent, 'such as') !== false;
    }

    private function hasClearPosition($content) {
        $lowerContent = strtolower($content);
        return strpos($lowerContent, 'i believe') !== false ||
               strpos($lowerContent, 'in my opinion') !== false ||
               strpos($lowerContent, 'i think') !== false;
    }

    private function convertToBandScore($percentage) {
        if ($percentage >= 95) return 9.0;
        if ($percentage >= 89) return 8.5;
        if ($percentage >= 83) return 8.0;
        if ($percentage >= 75) return 7.5;
        if ($percentage >= 67) return 7.0;
        if ($percentage >= 58) return 6.5;
        if ($percentage >= 50) return 6.0;
        if ($percentage >= 42) return 5.5;
        return 5.0;
    }

    private function getNextLevelRequirements($currentBand) {
        $requirements = [
            5.5 => 'Focus on basic task completion and clear organization',
            6.0 => 'Develop ideas more fully and use wider vocabulary',
            6.5 => 'Improve coherence and use more complex structures',
            7.0 => 'Show sophisticated vocabulary and flexible grammar',
            7.5 => 'Demonstrate precise language use and nuanced arguments',
            8.0 => 'Show complete command with natural, sophisticated expression',
            8.5 => 'Achieve near-native level accuracy and fluency'
        ];

        $nextBand = $currentBand + 0.5;
        return $requirements[$nextBand] ?? 'Continue practicing to maintain excellence';
    }
}