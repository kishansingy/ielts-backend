<?php

namespace App\Http\Controllers;

use App\Models\ListeningExercise;
use App\Models\ListeningQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ListeningController extends Controller
{
    /**
     * Display a listing of listening exercises
     */
    public function index(Request $request)
    {
        $query = ListeningExercise::with(['creator', 'questions']);
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        
        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $exercises = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json($exercises);
    }

    /**
     * Store a newly created listening exercise
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a|max:20480', // 20MB max
            'transcript' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:3600', // 1 second to 1 hour
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => ['required', Rule::in(['multiple_choice', 'true_false', 'fill_blank'])],
            'questions.*.correct_answer' => 'required|string',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.points' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        
        try {
            // Store the audio file
            $audioPath = $request->file('audio_file')->store('listening/audio', 'public');
            
            // Create the listening exercise
            $exercise = ListeningExercise::create([
                'title' => $request->title,
                'audio_file_path' => $audioPath,
                'transcript' => $request->transcript,
                'duration' => $request->duration,
                'difficulty_level' => $request->difficulty_level,
                'created_by' => Auth::id(),
            ]);

            // Create questions for the exercise
            foreach ($request->questions as $questionData) {
                $exercise->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'correct_answer' => $questionData['correct_answer'],
                    'options' => $questionData['options'] ?? null,
                    'points' => $questionData['points'],
                ]);
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Listening exercise created successfully',
                'exercise' => $exercise->load(['questions', 'creator'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            // Clean up uploaded file if exercise creation failed
            if (isset($audioPath)) {
                Storage::disk('public')->delete($audioPath);
            }
            
            return response()->json([
                'message' => 'Error creating listening exercise',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified listening exercise
     */
    public function show($id)
    {
        $exercise = ListeningExercise::with(['questions', 'creator'])->findOrFail($id);
        
        return response()->json($exercise);
    }
    
    /**
     * Update the specified listening exercise
     */
    public function update(Request $request, $id)
    {
        $exercise = ListeningExercise::findOrFail($id);
        
        // Check if user can update this exercise
        if ($exercise->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a|max:20480', // 20MB max
            'transcript' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:3600',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:listening_questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => ['required', Rule::in(['multiple_choice', 'true_false', 'fill_blank'])],
            'questions.*.correct_answer' => 'required|string',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.points' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        
        try {
            $updateData = [
                'title' => $request->title,
                'transcript' => $request->transcript,
                'duration' => $request->duration,
                'difficulty_level' => $request->difficulty_level,
            ];
            
            // Handle audio file update if provided
            if ($request->hasFile('audio_file')) {
                // Delete old audio file
                if ($exercise->audio_file_path) {
                    Storage::disk('public')->delete($exercise->audio_file_path);
                }
                
                // Store new audio file
                $audioPath = $request->file('audio_file')->store('listening/audio', 'public');
                $updateData['audio_file_path'] = $audioPath;
            }
            
            // Update the exercise
            $exercise->update($updateData);

            // Handle questions update/create/delete
            $existingQuestionIds = $exercise->questions()->pluck('id')->toArray();
            $submittedQuestionIds = collect($request->questions)->pluck('id')->filter()->toArray();
            
            // Delete questions not in the submitted list
            $questionsToDelete = array_diff($existingQuestionIds, $submittedQuestionIds);
            if (!empty($questionsToDelete)) {
                ListeningQuestion::whereIn('id', $questionsToDelete)->delete();
            }
            
            // Update or create questions
            foreach ($request->questions as $questionData) {
                if (isset($questionData['id'])) {
                    // Update existing question
                    $question = ListeningQuestion::find($questionData['id']);
                    if ($question && $question->listening_exercise_id === $exercise->id) {
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
                    $exercise->questions()->create([
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
                'message' => 'Listening exercise updated successfully',
                'exercise' => $exercise->fresh()->load(['questions', 'creator'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            // Clean up uploaded file if update failed
            if (isset($audioPath)) {
                Storage::disk('public')->delete($audioPath);
            }
            
            return response()->json([
                'message' => 'Error updating listening exercise',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified listening exercise
     */
    public function destroy($id)
    {
        $exercise = ListeningExercise::findOrFail($id);
        
        // Check if user can delete this exercise
        if ($exercise->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Delete associated audio file
        if ($exercise->audio_file_path) {
            Storage::disk('public')->delete($exercise->audio_file_path);
        }
        
        $exercise->delete();
        
        return response()->json(['message' => 'Listening exercise deleted successfully']);
    }
}