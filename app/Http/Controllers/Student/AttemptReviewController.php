<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\UserAnswer;
use App\Services\EnhancedEvaluationService;
use App\Services\FeedbackGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttemptReviewController extends Controller
{
    private $evaluationService;
    private $feedbackService;

    public function __construct(EnhancedEvaluationService $evaluationService, FeedbackGeneratorService $feedbackService)
    {
        $this->evaluationService = $evaluationService;
        $this->feedbackService = $feedbackService;
    }

    /**
     * Get detailed review of a specific attempt
     */
    public function getAttemptReview($attemptId)
    {
        $attempt = Attempt::with(['userAnswers', 'user'])
            ->where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Generate detailed feedback based on module type
        $feedback = $this->generateDetailedFeedback($attempt);

        return response()->json([
            'attempt' => $attempt,
            'feedback' => $feedback,
            'improvement_suggestions' => $this->getImprovementSuggestions($attempt),
            'band_specific_tips' => $this->getBandSpecificTips($attempt),
            'next_steps' => $this->getNextSteps($attempt)
        ]);
    }

    /**
     * Get all attempts for review
     */
    public function getUserAttempts(Request $request)
    {
        $query = Attempt::with(['userAnswers'])
            ->where('user_id', Auth::id())
            ->where('status', 'completed');

        // Filter by module type if provided
        if ($request->has('module_type')) {
            $query->where('module_type', $request->module_type);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $attempts = $query->orderBy('created_at', 'desc')->paginate(10);

        // Add summary data for each attempt
        foreach ($attempts as $attempt) {
            $attempt->summary = $this->getAttemptSummary($attempt);
        }

        return response()->json($attempts);
    }

    /**
     * Generate detailed feedback based on module type
     */
    private function generateDetailedFeedback($attempt)
    {
        switch ($attempt->module_type) {
            case 'reading':
                return $this->generateReadingFeedback($attempt);
            case 'listening':
                return $this->generateListeningFeedback($attempt);
            case 'writing':
                return $this->generateWritingFeedback($attempt);
            case 'speaking':
                return $this->generateSpeakingFeedback($attempt);
            default:
                return $this->generateGeneralFeedback($attempt);
        }
    }

    /**
     * Generate reading-specific feedback
     */
    private function generateReadingFeedback($attempt)
    {
        $userAnswers = $attempt->userAnswers;
        $correctAnswers = $userAnswers->where('is_correct', true)->count();
        $totalQuestions = $userAnswers->count();
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        // Analyze question types performance
        $questionTypeAnalysis = [];
        foreach ($userAnswers->groupBy('question_type') as $type => $answers) {
            $typeCorrect = $answers->where('is_correct', true)->count();
            $typeTotal = $answers->count();
            $questionTypeAnalysis[$type] = [
                'correct' => $typeCorrect,
                'total' => $typeTotal,
                'accuracy' => $typeTotal > 0 ? ($typeCorrect / $typeTotal) * 100 : 0
            ];
        }

        return [
            'overall_performance' => [
                'accuracy' => round($accuracy, 2),
                'band_score' => $this->calculateBandScore($accuracy, 'reading'),
                'time_spent' => $attempt->time_spent ?? 0,
                'questions_correct' => $correctAnswers,
                'questions_total' => $totalQuestions
            ],
            'question_type_analysis' => $questionTypeAnalysis,
            'strengths' => $this->identifyReadingStrengths($questionTypeAnalysis),
            'weaknesses' => $this->identifyReadingWeaknesses($questionTypeAnalysis),
            'detailed_answers' => $this->getDetailedAnswerAnalysis($userAnswers)
        ];
    }

    /**
     * Generate listening-specific feedback
     */
    private function generateListeningFeedback($attempt)
    {
        $userAnswers = $attempt->userAnswers;
        $correctAnswers = $userAnswers->where('is_correct', true)->count();
        $totalQuestions = $userAnswers->count();
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        return [
            'overall_performance' => [
                'accuracy' => round($accuracy, 2),
                'band_score' => $this->calculateBandScore($accuracy, 'listening'),
                'time_spent' => $attempt->time_spent ?? 0
            ],
            'listening_skills' => [
                'detail_recognition' => $this->analyzeDetailRecognition($userAnswers),
                'main_idea_comprehension' => $this->analyzeMainIdeaComprehension($userAnswers),
                'inference_ability' => $this->analyzeInferenceAbility($userAnswers)
            ],
            'common_errors' => $this->identifyListeningErrors($userAnswers),
            'improvement_areas' => $this->getListeningImprovementAreas($accuracy)
        ];
    }

    /**
     * Generate writing-specific feedback
     */
    private function generateWritingFeedback($attempt)
    {
        $userAnswers = $attempt->userAnswers;
        $writingAnswer = $userAnswers->first(); // Assuming one essay per attempt

        if (!$writingAnswer) {
            return ['error' => 'No writing submission found'];
        }

        $essayText = $writingAnswer->answer_text;
        $wordCount = str_word_count($essayText);

        return [
            'overall_performance' => [
                'word_count' => $wordCount,
                'estimated_band_score' => $this->estimateWritingBandScore($essayText),
                'time_spent' => $attempt->time_spent ?? 0
            ],
            'task_achievement' => [
                'score' => $this->analyzeTaskAchievement($essayText),
                'feedback' => $this->getTaskAchievementFeedback($essayText)
            ],
            'coherence_cohesion' => [
                'score' => $this->analyzeCoherenceCohesion($essayText),
                'feedback' => $this->getCoherenceFeedback($essayText)
            ],
            'lexical_resource' => [
                'score' => $this->analyzeLexicalResource($essayText),
                'feedback' => $this->getLexicalFeedback($essayText)
            ],
            'grammar_accuracy' => [
                'score' => $this->analyzeGrammarAccuracy($essayText),
                'feedback' => $this->getGrammarFeedback($essayText)
            ],
            'specific_suggestions' => $this->getWritingSpecificSuggestions($essayText, $wordCount)
        ];
    }

    /**
     * Generate speaking-specific feedback
     */
    private function generateSpeakingFeedback($attempt)
    {
        return [
            'overall_performance' => [
                'estimated_band_score' => 6.0, // Placeholder - would need speech analysis
                'time_spent' => $attempt->time_spent ?? 0
            ],
            'areas_assessed' => [
                'fluency_coherence' => [
                    'score' => 6.0,
                    'feedback' => 'Focus on reducing hesitation and improving flow'
                ],
                'lexical_resource' => [
                    'score' => 6.0,
                    'feedback' => 'Try to use more varied vocabulary'
                ],
                'grammatical_accuracy' => [
                    'score' => 6.0,
                    'feedback' => 'Work on complex sentence structures'
                ],
                'pronunciation' => [
                    'score' => 6.0,
                    'feedback' => 'Practice clear articulation'
                ]
            ],
            'note' => 'Advanced speech analysis coming soon for more detailed feedback'
        ];
    }

    /**
     * Get improvement suggestions based on performance
     */
    private function getImprovementSuggestions($attempt)
    {
        $suggestions = [];

        switch ($attempt->module_type) {
            case 'reading':
                $suggestions = [
                    'immediate' => [
                        'Practice skimming and scanning techniques',
                        'Focus on understanding question types',
                        'Improve time management - aim for 20 minutes per passage'
                    ],
                    'short_term' => [
                        'Read academic articles daily',
                        'Build vocabulary with context',
                        'Practice identifying main ideas vs details'
                    ],
                    'long_term' => [
                        'Develop critical thinking skills',
                        'Master all IELTS reading question types',
                        'Achieve consistent 8+ band scores'
                    ]
                ];
                break;

            case 'listening':
                $suggestions = [
                    'immediate' => [
                        'Practice note-taking while listening',
                        'Focus on key information identification',
                        'Improve spelling of common words'
                    ],
                    'short_term' => [
                        'Listen to English podcasts daily',
                        'Practice with different accents',
                        'Work on number and date recognition'
                    ],
                    'long_term' => [
                        'Develop prediction skills',
                        'Master all listening question formats',
                        'Achieve native-level comprehension'
                    ]
                ];
                break;

            case 'writing':
                $suggestions = [
                    'immediate' => [
                        'Plan your essay structure before writing',
                        'Practice writing within time limits',
                        'Focus on clear topic sentences'
                    ],
                    'short_term' => [
                        'Build academic vocabulary',
                        'Practice different essay types',
                        'Improve grammar accuracy'
                    ],
                    'long_term' => [
                        'Develop sophisticated argumentation',
                        'Master complex sentence structures',
                        'Achieve band 8+ writing consistently'
                    ]
                ];
                break;

            case 'speaking':
                $suggestions = [
                    'immediate' => [
                        'Practice speaking for 2 minutes without stopping',
                        'Record yourself and listen back',
                        'Focus on clear pronunciation'
                    ],
                    'short_term' => [
                        'Engage in daily English conversations',
                        'Practice describing pictures and situations',
                        'Work on fluency and natural rhythm'
                    ],
                    'long_term' => [
                        'Develop natural conversation skills',
                        'Master complex topic discussions',
                        'Achieve native-like fluency'
                    ]
                ];
                break;
        }

        return $suggestions;
    }

    /**
     * Get band-specific tips
     */
    private function getBandSpecificTips($attempt)
    {
        $currentBand = $this->estimateCurrentBand($attempt);
        $targetBand = $currentBand + 0.5; // Suggest next half band

        return [
            'current_estimated_band' => $currentBand,
            'target_band' => $targetBand,
            'tips_for_improvement' => $this->getTipsForBandImprovement($attempt->module_type, $currentBand, $targetBand)
        ];
    }

    /**
     * Get detailed feedback for an attempt
     */
    public function getDetailedFeedback($attemptId)
    {
        $attempt = Attempt::with(['userAnswers'])
            ->where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $feedback = $this->feedbackService->generateFeedback($attempt);

        return response()->json([
            'attempt_id' => $attemptId,
            'feedback' => $feedback,
            'generated_at' => now()
        ]);
    }

    /**
     * Get progress analytics for the user
     */
    public function getProgressAnalytics()
    {
        $userId = Auth::id();
        
        // Get attempts from last 30 days
        $attempts = Attempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->with('userAnswers')
            ->get();

        $analytics = [
            'total_attempts' => $attempts->count(),
            'modules_practiced' => $attempts->pluck('module_type')->unique()->count(),
            'average_accuracy' => $this->calculateAverageAccuracy($attempts),
            'improvement_trend' => $this->calculateImprovementTrend($attempts),
            'module_breakdown' => $this->getModuleBreakdown($attempts),
            'recent_performance' => $this->getRecentPerformance($attempts),
            'recommendations' => $this->getPersonalizedRecommendations($attempts)
        ];

        return response()->json($analytics);
    }

    /**
     * Get module-specific suggestions
     */
    public function getModuleSuggestions($module)
    {
        $userId = Auth::id();
        
        // Get recent attempts for this module
        $attempts = Attempt::where('user_id', $userId)
            ->where('module_type', $module)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with('userAnswers')
            ->get();

        if ($attempts->isEmpty()) {
            return response()->json([
                'module' => $module,
                'message' => 'No attempts found for this module',
                'general_suggestions' => $this->getGeneralModuleSuggestions($module)
            ]);
        }

        $suggestions = $this->generateModuleSpecificSuggestions($module, $attempts);

        return response()->json([
            'module' => $module,
            'attempts_analyzed' => $attempts->count(),
            'suggestions' => $suggestions,
            'last_updated' => now()
        ]);
    }

    /**
     * Get personalized study plan
     */
    public function getPersonalizedStudyPlan()
    {
        $userId = Auth::id();
        
        // Analyze user's performance across all modules
        $attempts = Attempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(60))
            ->with('userAnswers')
            ->get();

        $studyPlan = [
            'current_level_assessment' => $this->assessCurrentLevel($attempts),
            'weekly_schedule' => $this->generateWeeklySchedule($attempts),
            'priority_areas' => $this->identifyPriorityAreas($attempts),
            'resource_recommendations' => $this->getResourceRecommendations($attempts),
            'milestone_targets' => $this->setMilestoneTargets($attempts),
            'practice_intensity' => $this->recommendPracticeIntensity($attempts)
        ];

        return response()->json([
            'study_plan' => $studyPlan,
            'based_on_attempts' => $attempts->count(),
            'plan_duration' => '4 weeks',
            'created_at' => now()
        ]);
    }

    // Helper methods for analysis (simplified implementations)
    private function calculateBandScore($accuracy, $moduleType)
    {
        // Simplified band score calculation
        if ($accuracy >= 95) return 9.0;
        if ($accuracy >= 89) return 8.5;
        if ($accuracy >= 83) return 8.0;
        if ($accuracy >= 75) return 7.5;
        if ($accuracy >= 67) return 7.0;
        if ($accuracy >= 58) return 6.5;
        if ($accuracy >= 50) return 6.0;
        return 5.5;
    }

    private function getAttemptSummary($attempt)
    {
        $userAnswers = $attempt->userAnswers;
        $correctAnswers = $userAnswers->where('is_correct', true)->count();
        $totalQuestions = $userAnswers->count();
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        return [
            'accuracy' => round($accuracy, 2),
            'band_score' => $this->calculateBandScore($accuracy, $attempt->module_type),
            'time_spent' => $attempt->time_spent ?? 0,
            'questions_answered' => $totalQuestions,
            'status' => $attempt->status
        ];
    }

    // Placeholder methods for detailed analysis
    private function identifyReadingStrengths($analysis) { return ['Multiple choice questions', 'Time management']; }
    private function identifyReadingWeaknesses($analysis) { return ['Fill-in-the-blank questions', 'Inference questions']; }
    private function getDetailedAnswerAnalysis($answers) { return []; }
    private function analyzeDetailRecognition($answers) { return 75; }
    private function analyzeMainIdeaComprehension($answers) { return 80; }
    private function analyzeInferenceAbility($answers) { return 70; }
    private function identifyListeningErrors($answers) { return ['Spelling errors', 'Number format confusion']; }
    private function getListeningImprovementAreas($accuracy) { return ['Note-taking', 'Concentration']; }
    private function estimateWritingBandScore($text) { return 6.5; }
    private function analyzeTaskAchievement($text) { return 6.0; }
    private function getTaskAchievementFeedback($text) { return 'Address all parts of the task more fully'; }
    private function analyzeCoherenceCohesion($text) { return 6.5; }
    private function getCoherenceFeedback($text) { return 'Use more linking words'; }
    private function analyzeLexicalResource($text) { return 6.0; }
    private function getLexicalFeedback($text) { return 'Use more varied vocabulary'; }
    private function analyzeGrammarAccuracy($text) { return 6.0; }
    private function getGrammarFeedback($text) { return 'Check subject-verb agreement'; }
    private function getWritingSpecificSuggestions($text, $wordCount) { return ['Increase word count', 'Add more examples']; }
    private function estimateCurrentBand($attempt) { return 6.5; }
    private function getTipsForBandImprovement($module, $current, $target) { return ['Practice daily', 'Focus on weak areas']; }
    private function getRecommendedPractice($attempt) { return ['Daily practice sessions', 'Mock tests weekly']; }
    private function getStudyMaterials($module) { return ['Cambridge IELTS books', 'Online practice tests']; }
    private function getPracticeSchedule($module) { return ['30 minutes daily', '2 hours on weekends']; }
    private function getMilestoneGoals($attempt) { return ['Achieve 7.0 band in 2 weeks', 'Master question types']; }
    /**
     * Get next steps for improvement
     */
    private function getNextSteps($attempt)
    {
        return [
            'recommended_practice' => $this->getRecommendedPractice($attempt),
            'study_materials' => $this->getStudyMaterials($attempt->module_type),
            'practice_schedule' => $this->getPracticeSchedule($attempt->module_type),
            'milestone_goals' => $this->getMilestoneGoals($attempt)
        ];
    }

    // Additional helper methods for new functionality
    private function calculateAverageAccuracy($attempts)
    {
        if ($attempts->isEmpty()) return 0;
        
        $totalAccuracy = 0;
        foreach ($attempts as $attempt) {
            $userAnswers = $attempt->userAnswers;
            $correct = $userAnswers->where('is_correct', true)->count();
            $total = $userAnswers->count();
            $accuracy = $total > 0 ? ($correct / $total) * 100 : 0;
            $totalAccuracy += $accuracy;
        }
        
        return round($totalAccuracy / $attempts->count(), 2);
    }

    private function calculateImprovementTrend($attempts)
    {
        if ($attempts->count() < 2) return 'insufficient_data';
        
        $sortedAttempts = $attempts->sortBy('created_at');
        $firstHalf = $sortedAttempts->take(ceil($sortedAttempts->count() / 2));
        $secondHalf = $sortedAttempts->skip(ceil($sortedAttempts->count() / 2));
        
        $firstHalfAvg = $this->calculateAverageAccuracy($firstHalf);
        $secondHalfAvg = $this->calculateAverageAccuracy($secondHalf);
        
        $improvement = $secondHalfAvg - $firstHalfAvg;
        
        if ($improvement > 5) return 'improving';
        if ($improvement < -5) return 'declining';
        return 'stable';
    }

    private function getModuleBreakdown($attempts)
    {
        $breakdown = [];
        foreach (['reading', 'listening', 'writing', 'speaking'] as $module) {
            $moduleAttempts = $attempts->where('module_type', $module);
            $breakdown[$module] = [
                'attempts' => $moduleAttempts->count(),
                'average_accuracy' => $this->calculateAverageAccuracy($moduleAttempts),
                'last_attempt' => $moduleAttempts->sortByDesc('created_at')->first()?->created_at
            ];
        }
        return $breakdown;
    }

    private function getRecentPerformance($attempts)
    {
        return $attempts->sortByDesc('created_at')->take(5)->map(function ($attempt) {
            $userAnswers = $attempt->userAnswers;
            $correct = $userAnswers->where('is_correct', true)->count();
            $total = $userAnswers->count();
            
            return [
                'date' => $attempt->created_at,
                'module' => $attempt->module_type,
                'accuracy' => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
                'band_score' => $this->calculateBandScore($total > 0 ? ($correct / $total) * 100 : 0, $attempt->module_type)
            ];
        })->values();
    }

    private function getPersonalizedRecommendations($attempts)
    {
        $moduleBreakdown = $this->getModuleBreakdown($attempts);
        $recommendations = [];
        
        foreach ($moduleBreakdown as $module => $data) {
            if ($data['average_accuracy'] < 70) {
                $recommendations[] = "Focus more on {$module} practice - current accuracy: {$data['average_accuracy']}%";
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = "Great progress! Continue practicing all modules consistently.";
        }
        
        return $recommendations;
    }

    private function generateModuleSpecificSuggestions($module, $attempts)
    {
        $avgAccuracy = $this->calculateAverageAccuracy($attempts);
        
        $suggestions = [
            'current_performance' => [
                'average_accuracy' => $avgAccuracy,
                'attempts_count' => $attempts->count(),
                'trend' => $this->calculateImprovementTrend($attempts)
            ],
            'specific_suggestions' => $this->getModuleSpecificTips($module, $avgAccuracy),
            'practice_recommendations' => $this->getModulePracticeRecommendations($module, $avgAccuracy)
        ];
        
        return $suggestions;
    }

    private function getGeneralModuleSuggestions($module)
    {
        $suggestions = [
            'reading' => [
                'Start with basic skimming and scanning techniques',
                'Practice identifying main ideas',
                'Build academic vocabulary gradually'
            ],
            'listening' => [
                'Practice active listening with note-taking',
                'Focus on key information identification',
                'Work on different accent recognition'
            ],
            'writing' => [
                'Learn basic essay structure',
                'Practice planning before writing',
                'Focus on grammar accuracy'
            ],
            'speaking' => [
                'Practice speaking for 2 minutes continuously',
                'Record yourself and listen back',
                'Work on pronunciation clarity'
            ]
        ];
        
        return $suggestions[$module] ?? ['Start practicing this module regularly'];
    }

    private function getModuleSpecificTips($module, $accuracy)
    {
        // Return tips based on module and current accuracy level
        $tips = [];
        
        if ($accuracy < 60) {
            $tips[] = "Focus on fundamentals - your accuracy needs improvement";
            $tips[] = "Practice basic question types first";
        } elseif ($accuracy < 80) {
            $tips[] = "Good progress! Work on consistency";
            $tips[] = "Try more challenging materials";
        } else {
            $tips[] = "Excellent work! Focus on advanced techniques";
            $tips[] = "Prepare for higher band requirements";
        }
        
        return $tips;
    }

    private function getModulePracticeRecommendations($module, $accuracy)
    {
        return [
            'daily_practice_time' => $accuracy < 70 ? '45 minutes' : '30 minutes',
            'weekly_sessions' => $accuracy < 70 ? 6 : 4,
            'focus_areas' => $this->getFocusAreas($module, $accuracy)
        ];
    }

    private function getFocusAreas($module, $accuracy)
    {
        // Simplified focus areas based on module and accuracy
        $focusAreas = [
            'reading' => $accuracy < 70 ? ['Basic comprehension', 'Vocabulary'] : ['Advanced inference', 'Speed reading'],
            'listening' => $accuracy < 70 ? ['Note-taking', 'Basic comprehension'] : ['Complex conversations', 'Accent variety'],
            'writing' => $accuracy < 70 ? ['Grammar', 'Structure'] : ['Advanced vocabulary', 'Complex arguments'],
            'speaking' => $accuracy < 70 ? ['Fluency', 'Basic vocabulary'] : ['Advanced topics', 'Natural expressions']
        ];
        
        return $focusAreas[$module] ?? ['General practice'];
    }

    private function assessCurrentLevel($attempts)
    {
        $avgAccuracy = $this->calculateAverageAccuracy($attempts);
        $estimatedBand = $this->calculateBandScore($avgAccuracy, 'overall');
        
        return [
            'estimated_band' => $estimatedBand,
            'accuracy' => $avgAccuracy,
            'level_description' => $this->getBandDescription($estimatedBand)
        ];
    }

    private function getBandDescription($band)
    {
        if ($band >= 8.5) return 'Expert user with excellent command';
        if ($band >= 7.5) return 'Very good user with good operational command';
        if ($band >= 6.5) return 'Competent user with generally effective command';
        if ($band >= 5.5) return 'Modest user with partial command';
        return 'Limited user - needs significant improvement';
    }

    private function generateWeeklySchedule($attempts)
    {
        $moduleBreakdown = $this->getModuleBreakdown($attempts);
        
        // Identify weakest modules for more focus
        $weakestModules = collect($moduleBreakdown)
            ->sortBy('average_accuracy')
            ->take(2)
            ->keys()
            ->toArray();
        
        return [
            'monday' => ['focus' => $weakestModules[0] ?? 'reading', 'duration' => '45 minutes'],
            'tuesday' => ['focus' => 'listening', 'duration' => '30 minutes'],
            'wednesday' => ['focus' => $weakestModules[1] ?? 'writing', 'duration' => '45 minutes'],
            'thursday' => ['focus' => 'speaking', 'duration' => '30 minutes'],
            'friday' => ['focus' => 'mixed practice', 'duration' => '60 minutes'],
            'saturday' => ['focus' => 'mock test', 'duration' => '180 minutes'],
            'sunday' => ['focus' => 'review and analysis', 'duration' => '30 minutes']
        ];
    }

    private function identifyPriorityAreas($attempts)
    {
        $moduleBreakdown = $this->getModuleBreakdown($attempts);
        
        return collect($moduleBreakdown)
            ->filter(function ($data) {
                return $data['average_accuracy'] < 75;
            })
            ->sortBy('average_accuracy')
            ->keys()
            ->take(3)
            ->values()
            ->toArray();
    }

    private function getResourceRecommendations($attempts)
    {
        return [
            'books' => ['Cambridge IELTS 15-17', 'Official IELTS Practice Materials'],
            'online' => ['IELTS.org', 'British Council IELTS Preparation'],
            'apps' => ['IELTS Prep App', 'English Listening Practice'],
            'websites' => ['BBC Learning English', 'IELTS Liz']
        ];
    }

    private function setMilestoneTargets($attempts)
    {
        $currentLevel = $this->assessCurrentLevel($attempts);
        $targetBand = min(9.0, $currentLevel['estimated_band'] + 0.5);
        
        return [
            'week_1' => 'Establish consistent practice routine',
            'week_2' => 'Improve accuracy by 5% in weakest module',
            'week_3' => 'Complete full mock test with target band score',
            'week_4' => "Achieve band {$targetBand} consistently"
        ];
    }

    private function recommendPracticeIntensity($attempts)
    {
        $avgAccuracy = $this->calculateAverageAccuracy($attempts);
        
        if ($avgAccuracy < 60) {
            return [
                'intensity' => 'high',
                'daily_time' => '90 minutes',
                'focus' => 'fundamentals and basic skills'
            ];
        } elseif ($avgAccuracy < 80) {
            return [
                'intensity' => 'medium',
                'daily_time' => '60 minutes',
                'focus' => 'skill development and consistency'
            ];
        } else {
            return [
                'intensity' => 'maintenance',
                'daily_time' => '45 minutes',
                'focus' => 'advanced techniques and test strategies'
            ];
        }
    }
}