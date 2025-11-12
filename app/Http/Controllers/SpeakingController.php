<?php

namespace App\Http\Controllers;

use App\Models\SpeakingPrompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SpeakingController extends Controller
{
    /**
     * Display a listing of speaking prompts
     */
    public function index(Request $request)
    {
        $query = SpeakingPrompt::with(['creator']);
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        
        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $prompts = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json($prompts);
    }

    /**
     * Store a newly created speaking prompt
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'prompt_text' => 'required|string',
            'preparation_time' => 'required|integer|min:0|max:300', // 0 to 5 minutes
            'response_time' => 'required|integer|min:30|max:600', // 30 seconds to 10 minutes
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
        ]);

        $prompt = SpeakingPrompt::create([
            'title' => $request->title,
            'prompt_text' => $request->prompt_text,
            'preparation_time' => $request->preparation_time,
            'response_time' => $request->response_time,
            'difficulty_level' => $request->difficulty_level,
            'created_by' => Auth::id(),
        ]);
        
        return response()->json([
            'message' => 'Speaking prompt created successfully',
            'prompt' => $prompt->load('creator')
        ], 201);
    }

    /**
     * Display the specified speaking prompt
     */
    public function show($id)
    {
        $prompt = SpeakingPrompt::with(['creator'])->findOrFail($id);
        
        return response()->json($prompt);
    }

    /**
     * Update the specified speaking prompt
     */
    public function update(Request $request, $id)
    {
        $prompt = SpeakingPrompt::findOrFail($id);
        
        // Check if user can update this prompt
        if ($prompt->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'prompt_text' => 'required|string',
            'preparation_time' => 'required|integer|min:0|max:300',
            'response_time' => 'required|integer|min:30|max:600',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
        ]);

        $prompt->update([
            'title' => $request->title,
            'prompt_text' => $request->prompt_text,
            'preparation_time' => $request->preparation_time,
            'response_time' => $request->response_time,
            'difficulty_level' => $request->difficulty_level,
        ]);
        
        return response()->json([
            'message' => 'Speaking prompt updated successfully',
            'prompt' => $prompt->fresh()->load('creator')
        ]);
    }

    /**
     * Remove the specified speaking prompt
     */
    public function destroy($id)
    {
        $prompt = SpeakingPrompt::findOrFail($id);
        
        // Check if user can delete this prompt
        if ($prompt->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $prompt->delete();
        
        return response()->json(['message' => 'Speaking prompt deleted successfully']);
    }
}