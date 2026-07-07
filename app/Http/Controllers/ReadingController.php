<?php

namespace App\Http\Controllers;

use App\Models\ReadingPassage;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReadingController extends Controller
{
    /**
     * Display a listing of reading passages
     */
    public function index(Request $request)
    {
        $query = ReadingPassage::with(['creator', 'questions']);
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        
        // Filter by band level if provided
        if ($request->has('band_level')) {
            $query->where('band_level', $request->band_level);
        }
        
        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $passages = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json($passages);
    }

    /**
     * Store a newly created reading passage
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'band_level' => ['required', Rule::in(['band6', 'band7', 'band8', 'band9'])],
            'time_limit' => 'required|integer|min:1|max:120',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => ['required', Rule::in(['multiple_choice', 'true_false', 'fill_blank'])],
            'questions.*.correct_answer' => 'required|string',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.points' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        
        try {
            // Create the reading passage
            $passage = ReadingPassage::create([
                'title' => $request->title,
                'content' => $request->content,
                'difficulty_level' => $request->difficulty_level,
                'band_level' => $request->band_level,
                'time_limit' => $request->time_limit,
                'created_by' => Auth::id(),
            ]);

            // Create questions for the passage
            foreach ($request->questions as $questionData) {
                $passage->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'correct_answer' => $questionData['correct_answer'],
                    'options' => $questionData['options'] ?? null,
                    'points' => $questionData['points'],
                ]);
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Reading passage created successfully',
                'passage' => $passage->load(['questions', 'creator'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error creating reading passage',
                'error' => $e->getMessage()
            ], 500);
        }
    }    
/**
     * Display the specified reading passage
     */
    public function show($id)
    {
        $passage = ReadingPassage::with(['questions', 'creator'])->findOrFail($id);
        
        return response()->json($passage);
    }

    /**
     * Update the specified reading passage
     */
    public function update(Request $request, $id)
    {
        $passage = ReadingPassage::findOrFail($id);
        
        // Check if user can update this passage
        if ($passage->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'band_level' => ['required', Rule::in(['band6', 'band7', 'band8', 'band9'])],
            'time_limit' => 'required|integer|min:1|max:120',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => ['required', Rule::in(['multiple_choice', 'true_false', 'fill_blank'])],
            'questions.*.correct_answer' => 'required|string',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.points' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        
        try {
            // Update the passage
            $passage->update([
                'title' => $request->title,
                'content' => $request->content,
                'difficulty_level' => $request->difficulty_level,
                'band_level' => $request->band_level,
                'time_limit' => $request->time_limit,
            ]);

            // Handle questions update/create/delete
            $existingQuestionIds = $passage->questions()->pluck('id')->toArray();
            $submittedQuestionIds = collect($request->questions)->pluck('id')->filter()->toArray();
            
            // Delete questions not in the submitted list
            $questionsToDelete = array_diff($existingQuestionIds, $submittedQuestionIds);
            if (!empty($questionsToDelete)) {
                Question::whereIn('id', $questionsToDelete)->delete();
            }
            
            // Update or create questions
            foreach ($request->questions as $questionData) {
                if (isset($questionData['id'])) {
                    // Update existing question
                    $question = Question::find($questionData['id']);
                    if ($question && $question->passage_id === $passage->id) {
                        $question->update([
                            'question_text' => $questionData['question_text'],
                            'question_type' => $questionData['question_type'],
                            'correct_answer' => $questionData['correct_answer'],
                            'options' => $questionData['options'] ?? null,
                            'points' => $questionData['points'],
                        ]);
                    }
                } else {
                    // Create new question
                    $passage->questions()->create([
                        'question_text' => $questionData['question_text'],
                        'question_type' => $questionData['question_type'],
                        'correct_answer' => $questionData['correct_answer'],
                        'options' => $questionData['options'] ?? null,
                        'points' => $questionData['points'],
                    ]);
                }
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Reading passage updated successfully',
                'passage' => $passage->fresh()->load(['questions', 'creator'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error updating reading passage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified reading passage
     */
    public function destroy($id)
    {
        $passage = ReadingPassage::findOrFail($id);
        
        // Check if user can delete this passage
        if ($passage->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $passage->delete();
        
        return response()->json(['message' => 'Reading passage deleted successfully']);
    }
}