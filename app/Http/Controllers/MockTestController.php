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
        
        // Filter by user's band level if not admin
        if (!$user->isAdmin()) {
            $query->accessibleByUser($user)->available();
        }
        
        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $mockTests = $query->orderBy('created_at', 'desc')->get();
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
        
        // Check if user can access this mock test
        if (!$user->isAdmin() && !$user->canAccessBandLevel($mockTest->band_level)) {
            return response()->json([
                'error' => 'Access denied. This mock test is for ' . $mockTest->band_level . ' level.'
            ], 403);
        }
        
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
        
        // Check if user can access this mock test
        if (!$user->isAdmin() && !$user->canAccessBandLevel($mockTest->band_level)) {
            return response()->json([
                'error' => 'Access denied. This mock test is for ' . $mockTest->band_level . ' level.'
            ], 403);
        }
        
        $attempt = MockTestAttempt::create([
            'user_id' => $user->id,
            'mock_test_id' => $mockTest->id,
            'started_at' => now(),
        ]);
        
        return response()->json($attempt);
    }

    public function submitAttempt(Request $request, $attemptId)
    {
        $attempt = MockTestAttempt::findOrFail($attemptId);
        
        $validated = $request->validate([
            'reading_score' => 'nullable|numeric',
            'writing_score' => 'nullable|numeric',
            'listening_score' => 'nullable|numeric',
            'speaking_score' => 'nullable|numeric',
        ]);
        
        $totalScore = ($validated['reading_score'] ?? 0) + 
                     ($validated['writing_score'] ?? 0) + 
                     ($validated['listening_score'] ?? 0) + 
                     ($validated['speaking_score'] ?? 0);
        
        $overallBand = $totalScore / 4; // Simple average for IELTS band
        
        $attempt->update([
            'completed_at' => now(),
            'time_spent' => now()->diffInSeconds($attempt->started_at),
            'reading_score' => $validated['reading_score'] ?? 0,
            'writing_score' => $validated['writing_score'] ?? 0,
            'listening_score' => $validated['listening_score'] ?? 0,
            'speaking_score' => $validated['speaking_score'] ?? 0,
            'total_score' => $totalScore,
            'overall_band' => $overallBand,
        ]);
        
        return response()->json($attempt);
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
