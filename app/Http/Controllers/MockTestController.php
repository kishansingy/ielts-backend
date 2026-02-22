<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\MockTestAttempt;
use App\Models\ReadingPassage;
use App\Models\WritingTask;
use App\Models\ListeningExercise;
use App\Models\SpeakingPrompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MockTestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = MockTest::with('sections');
        
        // Filter by band level if provided (for admin filtering)
        if ($request->has('band_level') && $user->isAdmin()) {
            $query->byBandLevel($request->band_level);
        }
        
        // Allow students to see all bands if all_bands parameter is true
        if ($request->has('all_bands') && $request->all_bands === 'true') {
            $query->where('is_active', true)->available();
        } else {
            // Filter by user's band level if not admin and all_bands not requested
            if (!$user->isAdmin()) {
                $query->accessibleByUser($user)->available();
            }
        }
        
        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $mockTests = $query->orderBy('band_level', 'asc')
                          ->orderBy('created_at', 'desc')
                          ->get();
        return response()->json($mockTests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'band_level' => 'required|in:band6,band7,band8,band9',
            'duration_minutes' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'sections' => 'required|array',
            'sections.*.module_type' => 'required|in:reading,writing,listening,speaking',
            'sections.*.content_id' => 'required|integer',
            'sections.*.duration_minutes' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $mockTest = MockTest::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'band_level' => $validated['band_level'],
                'duration_minutes' => $validated['duration_minutes'],
                'is_active' => $validated['is_active'] ?? true,
                'available_from' => $validated['available_from'] ?? null,
                'available_until' => $validated['available_until'] ?? null,
            ]);

            foreach ($validated['sections'] as $index => $section) {
                $contentType = $this->getContentType($section['module_type']);
                
                MockTestSection::create([
                    'mock_test_id' => $mockTest->id,
                    'module_type' => $section['module_type'],
                    'content_id' => $section['content_id'],
                    'content_type' => $contentType,
                    'order' => $index,
                    'duration_minutes' => $section['duration_minutes'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json($mockTest->load('sections'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create mock test', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $mockTest = MockTest::with(['sections'])->findOrFail($id);
        
        // Allow all users to view any band level mock test
        // No restriction based on user's band level
        
        // Load actual content for each section
        foreach ($mockTest->sections as $section) {
            $section->load('content');
        }
        
        return response()->json($mockTest);
    }

    public function update(Request $request, $id)
    {
        $mockTest = MockTest::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'band_level' => 'in:band6,band7,band8,band9',
            'duration_minutes' => 'integer|min:1',
            'is_active' => 'boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
        ]);

        $mockTest->update($validated);
        return response()->json($mockTest);
    }

    public function destroy($id)
    {
        $mockTest = MockTest::findOrFail($id);
        $mockTest->delete();
        return response()->json(['message' => 'Mock test deleted successfully']);
    }

    public function getAvailableContent(Request $request)
    {
        $moduleType = $request->query('module_type');
        
        $content = [];
        switch ($moduleType) {
            case 'reading':
                $content = ReadingPassage::with('questions')->get();
                break;
            case 'writing':
                $content = WritingTask::all();
                break;
            case 'listening':
                $content = ListeningExercise::with('questions')->get();
                break;
            case 'speaking':
                $content = SpeakingPrompt::all();
                break;
        }
        
        return response()->json($content);
    }

    private function getContentType($moduleType)
    {
        $types = [
            'reading' => 'App\Models\ReadingPassage',
            'writing' => 'App\Models\WritingTask',
            'listening' => 'App\Models\ListeningExercise',
            'speaking' => 'App\Models\SpeakingPrompt',
        ];
        
        return $types[$moduleType];
    }

    public function startAttempt(Request $request, $id)
    {
        $user = $request->user();
        $mockTest = MockTest::findOrFail($id);
        
        // Allow all users to attempt any band level mock test
        // No restriction based on user's band level
        
        $attempt = MockTestAttempt::create([
            'user_id' => $user->id,
            'mock_test_id' => $mockTest->id,
            'started_at' => now(),
        ]);
        
        return response()->json($attempt);
    }

    public function submitAttempt(Request $request, $attemptId)
    {
        $attempt = MockTestAttempt::with('mockTest.sections')->findOrFail($attemptId);
        
        $validated = $request->validate([
            'answers' => 'nullable|array',
            'writing_response' => 'nullable|string',
            'audio_recording' => 'nullable|string',
            'time_spent' => 'nullable|integer',
        ]);
        
        $answers = $validated['answers'] ?? [];
        $scores = [
            'reading' => 0,
            'listening' => 0,
            'writing' => 0,
            'speaking' => 0,
        ];
        $totalQuestions = [
            'reading' => 0,
            'listening' => 0,
        ];
        
        // Grade Reading and Listening sections (objective answers)
        foreach ($attempt->mockTest->sections as $section) {
            if ($section->module_type === 'reading') {
                $passage = \App\Models\ReadingPassage::with('questions')->find($section->content_id);
                if ($passage && $passage->questions) {
                    foreach ($passage->questions as $question) {
                        $totalQuestions['reading']++;
                        $userAnswer = $answers[$question->id] ?? '';
                        
                        // Make correct_answer visible for grading
                        $question->makeVisible('correct_answer');
                        
                        if ($this->compareAnswers($userAnswer, $question->correct_answer)) {
                            $scores['reading']++;
                        }
                    }
                }
            } elseif ($section->module_type === 'listening') {
                $exercise = \App\Models\ListeningExercise::with('questions')->find($section->content_id);
                if ($exercise && $exercise->questions) {
                    foreach ($exercise->questions as $question) {
                        $totalQuestions['listening']++;
                        $userAnswer = $answers[$question->id] ?? '';
                        
                        // Make correct_answer visible for grading
                        $question->makeVisible('correct_answer');
                        
                        if ($this->compareAnswers($userAnswer, $question->correct_answer)) {
                            $scores['listening']++;
                        }
                    }
                }
            }
        }
        
        // Calculate band scores (IELTS scoring: 0-40 questions mapped to 0-9 band)
        $readingBand = $this->calculateBandScore($scores['reading'], $totalQuestions['reading']);
        $listeningBand = $this->calculateBandScore($scores['listening'], $totalQuestions['listening']);
        
        // Evaluate writing based on word count and effort
        $writingBand = $this->evaluateWriting($validated['writing_response'] ?? '');
        
        // Evaluate speaking based on audio presence
        $speakingBand = $this->evaluateSpeaking($validated['audio_recording'] ?? null);
        
        $overallBand = ($readingBand + $listeningBand + $writingBand + $speakingBand) / 4;
        $overallBand = round($overallBand * 2) / 2; // Round to nearest 0.5
        
        $attempt->update([
            'completed_at' => now(),
            'time_spent' => $validated['time_spent'] ?? now()->diffInSeconds($attempt->started_at),
            'reading_score' => $readingBand,
            'writing_score' => $writingBand,
            'listening_score' => $listeningBand,
            'speaking_score' => $speakingBand,
            'total_score' => $scores['reading'] + $scores['listening'],
            'overall_band' => $overallBand,
        ]);
        
        return response()->json([
            'attempt' => $attempt,
            'detailed_scores' => [
                'reading' => [
                    'correct' => $scores['reading'],
                    'total' => $totalQuestions['reading'],
                    'band' => $readingBand
                ],
                'listening' => [
                    'correct' => $scores['listening'],
                    'total' => $totalQuestions['listening'],
                    'band' => $listeningBand
                ],
                'writing' => [
                    'band' => $writingBand,
                    'note' => 'Requires manual grading'
                ],
                'speaking' => [
                    'band' => $speakingBand,
                    'note' => 'Requires manual grading'
                ]
            ]
        ]);
    }
    
    /**
     * Compare user answer with correct answer (case-insensitive, trimmed)
     */
    private function compareAnswers($userAnswer, $correctAnswer)
    {
        $userAnswer = strtolower(trim($userAnswer));
        $correctAnswer = strtolower(trim($correctAnswer));
        
        // Exact match
        if ($userAnswer === $correctAnswer) {
            return true;
        }
        
        // Check if correct answer contains multiple acceptable answers (separated by |)
        $acceptableAnswers = explode('|', $correctAnswer);
        foreach ($acceptableAnswers as $acceptable) {
            if ($userAnswer === strtolower(trim($acceptable))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate IELTS band score from correct answers
     * Simplified mapping: percentage to band score
     */
    private function calculateBandScore($correct, $total)
    {
        if ($total == 0) return 0;
        
        $percentage = ($correct / $total) * 100;
        
        // IELTS band score mapping (approximate)
        if ($percentage >= 90) return 9.0;
        if ($percentage >= 85) return 8.5;
        if ($percentage >= 80) return 8.0;
        if ($percentage >= 75) return 7.5;
        if ($percentage >= 70) return 7.0;
        if ($percentage >= 65) return 6.5;
        if ($percentage >= 60) return 6.0;
        if ($percentage >= 55) return 5.5;
        if ($percentage >= 50) return 5.0;
        if ($percentage >= 45) return 4.5;
        if ($percentage >= 40) return 4.0;
        if ($percentage >= 35) return 3.5;
        if ($percentage >= 30) return 3.0;
        if ($percentage >= 25) return 2.5;
        if ($percentage >= 20) return 2.0;
        if ($percentage >= 15) return 1.5;
        if ($percentage >= 10) return 1.0;
        return 0.5;
    }
    
    /**
     * Evaluate writing response based on word count and basic quality checks
     */
    private function evaluateWriting($writingResponse)
    {
        if (empty($writingResponse)) {
            return 0;
        }
        
        $wordCount = str_word_count($writingResponse);
        $uniqueWords = count(array_unique(str_word_count(strtolower($writingResponse), 1)));
        $sentences = preg_split('/[.!?]+/', $writingResponse, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);
        
        // Check for copy-paste (repeated text)
        $words = str_word_count(strtolower($writingResponse), 1);
        $wordFrequency = array_count_values($words);
        $maxRepetition = max($wordFrequency);
        $repetitionRatio = $maxRepetition / $wordCount;
        
        // Penalize if too much repetition (likely copy-paste)
        if ($repetitionRatio > 0.3) {
            return 2.0; // Very low score for copy-paste
        }
        
        // Score based on word count (IELTS requires 150 for Task 1, 250 for Task 2)
        $score = 0;
        if ($wordCount < 50) {
            $score = 1.0;
        } elseif ($wordCount < 100) {
            $score = 2.5;
        } elseif ($wordCount < 150) {
            $score = 3.5;
        } elseif ($wordCount < 200) {
            $score = 4.5;
        } elseif ($wordCount < 250) {
            $score = 5.0;
        } else {
            $score = 5.5; // Base score for adequate length
        }
        
        // Bonus for vocabulary diversity
        $vocabularyRatio = $uniqueWords / $wordCount;
        if ($vocabularyRatio > 0.7) {
            $score += 0.5;
        }
        
        // Bonus for sentence variety
        if ($sentenceCount >= 5 && $wordCount / $sentenceCount > 10) {
            $score += 0.5;
        }
        
        return min($score, 6.5); // Cap at 6.5 (needs AI/human for higher scores)
    }
    
    /**
     * Evaluate speaking based on audio recording presence
     */
    private function evaluateSpeaking($audioRecording)
    {
        if (empty($audioRecording)) {
            return 0;
        }
        
        // Basic check: if audio exists, give base score
        // In production, this would use speech-to-text and AI evaluation
        $audioSize = strlen($audioRecording);
        
        if ($audioSize < 1000) {
            return 2.0; // Very short recording
        } elseif ($audioSize < 10000) {
            return 3.5; // Short recording
        } elseif ($audioSize < 50000) {
            return 4.5; // Moderate recording
        } else {
            return 5.5; // Adequate recording (needs AI/human for accurate scoring)
        }
    }

    public function myAttempts(Request $request)
    {
        $attempts = MockTestAttempt::with('mockTest')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($attempts);
    }

    public function getAttemptResults(Request $request, $attemptId)
    {
        $attempt = MockTestAttempt::with(['mockTest', 'user'])
            ->where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        
        return response()->json($attempt);
    }
}
