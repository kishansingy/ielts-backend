<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\UserAnswer;

class FeedbackGeneratorService
{
    /**
     * Generate comprehensive feedback for an attempt
     */
    public function generateFeedback(Attempt $attempt): array
    {
        return [
            'performance_analysis' => $this->analyzePerformance($attempt),
            'improvement_suggestions' => $this->generateImprovementSuggestions($attempt),
            'band_progression' => $this->analyzeBandProgression($attempt),
            'study_plan' => $this->generateStudyPlan($attempt),
            'resources' => $this->recommendResources($attempt)
        ];
    }

    /**
     * Analyze overall performance
     */
    private function analyzePerformance(Attempt $attempt): array
    {
        $userAnswers = $attempt->userAnswers;
        $totalQuestions = $userAnswers->count();
        $correctAnswers = $userAnswers->where('is_correct', true)->count();
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        return [
            'overall_score' => round($accuracy, 2),
            'band_score' => $this->calculateBandScore($accuracy, $attempt->module_type),
            'time_efficiency' => $this->analyzeTimeEfficiency($attempt),
            'question_type_breakdown' => $this->analyzeQuestionTypes($userAnswers),
            'difficulty_analysis' => $this->analyzeDifficultyHandling($userAnswers)
        ];
    }

    /**
     * Generate specific improvement suggestions
     */
    private function generateImprovementSuggestions(Attempt $attempt): array
    {
        $moduleType = $attempt->module_type;
        $performance = $this->analyzePerformance($attempt);
        
        $suggestions = [
            'immediate_actions' => [],
            'weekly_goals' => [],
            'monthly_targets' => [],
            'skill_specific' => []
        ];

        switch ($moduleType) {
            case 'reading':
                $suggestions = $this->getReadingImprovementSuggestions($performance);
                break;
            case 'listening':
                $suggestions = $this->getListeningImprovementSuggestions($performance);
                break;
            case 'writing':
                $suggestions = $this->getWritingImprovementSuggestions($performance);
                break;
            case 'speaking':
                $suggestions = $this->getSpeakingImprovementSuggestions($performance);
                break;
        }

        return $suggestions;
    }

    /**
     * Reading-specific improvement suggestions
     */
    private function getReadingImprovementSuggestions(array $performance): array
    {
        $bandScore = $performance['band_score'];
        
        if ($bandScore < 6.0) {
            return [
                'immediate_actions' => [
                    'Practice basic skimming and scanning techniques',
                    'Focus on understanding main ideas before details',
                    'Build essential academic vocabulary (500 words)',
                    'Practice time management - 20 minutes per passage'
                ],
                'weekly_goals' => [
                    'Complete 3 reading passages daily',
                    'Learn 50 new academic words',
                    'Practice all question types',
                    'Improve reading speed to 250 words/minute'
                ],
                'monthly_targets' => [
                    'Achieve consistent 6.0+ band scores',
                    'Master basic question types',
                    'Build vocabulary to 2000+ academic words',
                    'Develop effective note-taking strategies'
                ],
                'skill_specific' => [
                    'True/False/Not Given' => 'Learn to distinguish between False and Not Given',
                    'Multiple Choice' => 'Practice eliminating wrong options',
                    'Fill in the Blanks' => 'Focus on grammar and word forms',
                    'Matching' => 'Improve scanning for specific information'
                ]
            ];
        } elseif ($bandScore < 7.0) {
            return [
                'immediate_actions' => [
                    'Practice inference and implication questions',
                    'Work on complex sentence understanding',
                    'Improve speed without losing accuracy',
                    'Focus on academic text structures'
                ],
                'weekly_goals' => [
                    'Complete 5 passages daily with analysis',
                    'Study advanced vocabulary in context',
                    'Practice under timed conditions',
                    'Analyze mistakes thoroughly'
                ],
                'monthly_targets' => [
                    'Achieve consistent 7.0+ band scores',
                    'Master all question types',
                    'Read academic journals regularly',
                    'Develop critical reading skills'
                ],
                'skill_specific' => [
                    'Summary Completion' => 'Practice identifying key information',
                    'Paragraph Headings' => 'Focus on main idea identification',
                    'Writer\'s Views' => 'Understand author\'s opinion vs facts',
                    'Complex Matching' => 'Improve detailed comprehension'
                ]
            ];
        } else {
            return [
                'immediate_actions' => [
                    'Focus on the most challenging question types',
                    'Practice with authentic academic texts',
                    'Work on speed reading techniques',
                    'Develop prediction strategies'
                ],
                'weekly_goals' => [
                    'Maintain high accuracy under pressure',
                    'Study specialized academic vocabulary',
                    'Practice with varied text types',
                    'Mentor other students'
                ],
                'monthly_targets' => [
                    'Achieve consistent 8.0+ band scores',
                    'Master advanced reading strategies',
                    'Read extensively in various fields',
                    'Develop teaching abilities'
                ],
                'skill_specific' => [
                    'Advanced Inference' => 'Master subtle meaning interpretation',
                    'Complex Vocabulary' => 'Handle specialized terminology',
                    'Text Organization' => 'Understand sophisticated structures',
                    'Critical Analysis' => 'Evaluate arguments and evidence'
                ]
            ];
        }
    }

    /**
     * Listening-specific improvement suggestions
     */
    private function getListeningImprovementSuggestions(array $performance): array
    {
        return [
            'immediate_actions' => [
                'Practice active listening with note-taking',
                'Focus on key information identification',
                'Improve spelling of common words',
                'Work on number and date recognition'
            ],
            'weekly_goals' => [
                'Listen to English content 2 hours daily',
                'Practice with different accents',
                'Complete 10 listening exercises',
                'Improve concentration stamina'
            ],
            'monthly_targets' => [
                'Achieve target band score',
                'Master all listening question types',
                'Develop prediction skills',
                'Handle complex conversations'
            ],
            'skill_specific' => [
                'Form Completion' => 'Practice spelling and format accuracy',
                'Multiple Choice' => 'Improve option elimination skills',
                'Map/Diagram' => 'Develop spatial listening skills',
                'Matching' => 'Enhance detail recognition'
            ]
        ];
    }

    /**
     * Writing-specific improvement suggestions
     */
    private function getWritingImprovementSuggestions(array $performance): array
    {
        return [
            'immediate_actions' => [
                'Plan essay structure before writing',
                'Practice writing within time limits',
                'Focus on clear topic sentences',
                'Improve grammar accuracy'
            ],
            'weekly_goals' => [
                'Write 2 essays with feedback',
                'Study model answers',
                'Build academic vocabulary',
                'Practice different essay types'
            ],
            'monthly_targets' => [
                'Achieve consistent band improvement',
                'Master essay organization',
                'Develop sophisticated arguments',
                'Use complex sentence structures'
            ],
            'skill_specific' => [
                'Task Achievement' => 'Address all parts of the question fully',
                'Coherence & Cohesion' => 'Use linking words effectively',
                'Lexical Resource' => 'Expand vocabulary range and accuracy',
                'Grammar' => 'Master complex structures with accuracy'
            ]
        ];
    }

    /**
     * Speaking-specific improvement suggestions
     */
    private function getSpeakingImprovementSuggestions(array $performance): array
    {
        return [
            'immediate_actions' => [
                'Practice speaking for 2 minutes without stopping',
                'Record yourself and analyze',
                'Focus on clear pronunciation',
                'Work on natural rhythm'
            ],
            'weekly_goals' => [
                'Engage in daily conversations',
                'Practice all speaking parts',
                'Work on fluency exercises',
                'Expand topic vocabulary'
            ],
            'monthly_targets' => [
                'Achieve natural conversation flow',
                'Master complex topic discussions',
                'Develop confident delivery',
                'Handle unexpected questions'
            ],
            'skill_specific' => [
                'Fluency & Coherence' => 'Reduce hesitation and improve flow',
                'Lexical Resource' => 'Use varied and appropriate vocabulary',
                'Grammar' => 'Use complex structures accurately',
                'Pronunciation' => 'Work on clarity and natural stress'
            ]
        ];
    }

    /**
     * Analyze band progression potential
     */
    private function analyzeBandProgression(Attempt $attempt): array
    {
        $currentBand = $this->calculateBandScore(75, $attempt->module_type); // Placeholder
        
        return [
            'current_band' => $currentBand,
            'next_target' => $currentBand + 0.5,
            'time_to_improve' => $this->estimateImprovementTime($currentBand),
            'key_focus_areas' => $this->getKeyFocusAreas($attempt),
            'milestone_plan' => $this->createMilestonePlan($currentBand)
        ];
    }

    /**
     * Generate personalized study plan
     */
    private function generateStudyPlan(Attempt $attempt): array
    {
        return [
            'daily_routine' => [
                'morning' => ['Vocabulary building (15 min)', 'Reading practice (30 min)'],
                'afternoon' => ['Listening practice (20 min)', 'Grammar review (15 min)'],
                'evening' => ['Writing practice (30 min)', 'Speaking practice (20 min)']
            ],
            'weekly_schedule' => [
                'monday' => 'Focus on Reading skills',
                'tuesday' => 'Listening practice day',
                'wednesday' => 'Writing improvement',
                'thursday' => 'Speaking practice',
                'friday' => 'Mixed skills practice',
                'saturday' => 'Mock test day',
                'sunday' => 'Review and analysis'
            ],
            'monthly_goals' => [
                'week_1' => 'Establish routine and baseline',
                'week_2' => 'Focus on weak areas',
                'week_3' => 'Intensive practice',
                'week_4' => 'Mock tests and evaluation'
            ]
        ];
    }

    /**
     * Recommend study resources
     */
    private function recommendResources(Attempt $attempt): array
    {
        $moduleType = $attempt->module_type;
        
        $resources = [
            'books' => [],
            'online_platforms' => [],
            'mobile_apps' => [],
            'practice_materials' => [],
            'video_tutorials' => []
        ];

        switch ($moduleType) {
            case 'reading':
                $resources = [
                    'books' => [
                        'Cambridge IELTS 15-17 (Official Practice Tests)',
                        'IELTS Reading Recent Actual Tests',
                        'Academic Vocabulary in Use'
                    ],
                    'online_platforms' => [
                        'IELTS.org Official Practice',
                        'British Council IELTS Preparation',
                        'IELTSLiz Reading Tips'
                    ],
                    'mobile_apps' => [
                        'IELTS Prep App by British Council',
                        'IELTS Reading by IELTSPractice',
                        'Vocabulary Builder'
                    ],
                    'practice_materials' => [
                        'Academic journals and articles',
                        'Scientific American articles',
                        'BBC Learning English'
                    ]
                ];
                break;
                
            case 'listening':
                $resources = [
                    'books' => [
                        'Cambridge IELTS Listening Tests',
                        'IELTS Listening Recent Actual Tests'
                    ],
                    'online_platforms' => [
                        'BBC Learning English',
                        'TED Talks',
                        'IELTS.org Listening Practice'
                    ],
                    'mobile_apps' => [
                        'IELTS Listening by IELTSPractice',
                        'English Listening Practice'
                    ]
                ];
                break;
                
            case 'writing':
                $resources = [
                    'books' => [
                        'IELTS Writing Task 1 & 2 by Simon',
                        'Cambridge Grammar for IELTS'
                    ],
                    'online_platforms' => [
                        'IELTS Simon Writing Tips',
                        'IELTS Advantage Writing Course'
                    ]
                ];
                break;
                
            case 'speaking':
                $resources = [
                    'books' => [
                        'IELTS Speaking Actual Tests',
                        'English Pronunciation in Use'
                    ],
                    'online_platforms' => [
                        'IELTS Speaking Practice Online',
                        'Cambly for Speaking Practice'
                    ]
                ];
                break;
        }

        return $resources;
    }

    // Helper methods
    private function calculateBandScore($accuracy, $moduleType)
    {
        if ($accuracy >= 95) return 9.0;
        if ($accuracy >= 89) return 8.5;
        if ($accuracy >= 83) return 8.0;
        if ($accuracy >= 75) return 7.5;
        if ($accuracy >= 67) return 7.0;
        if ($accuracy >= 58) return 6.5;
        if ($accuracy >= 50) return 6.0;
        return 5.5;
    }

    private function analyzeTimeEfficiency($attempt) { return 85; }
    private function analyzeQuestionTypes($answers) { return []; }
    private function analyzeDifficultyHandling($answers) { return 'Good'; }
    private function estimateImprovementTime($band) { return '2-4 weeks'; }
    private function getKeyFocusAreas($attempt) { return ['Vocabulary', 'Speed']; }
    private function createMilestonePlan($band) { return []; }
}