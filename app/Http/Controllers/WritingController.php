<?php

namespace App\Http\Controllers;

use App\Models\WritingTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WritingController extends Controller
{
    /**
     * Display a listing of writing tasks
     */
    public function index(Request $request)
    {
        $query = WritingTask::with(['creator']);
        
        // Filter by task type if provided
        if ($request->has('task_type')) {
            $query->byType($request->task_type);
        }
        
        // Filter by band level if provided
        if ($request->has('band_level')) {
            $query->byBandLevel($request->band_level);
        }
        
        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json($tasks);
    }

    /**
     * Store a newly created writing task
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'task_type' => ['required', Rule::in(['task1', 'task2'])],
            'prompt' => 'required|string',
            'instructions' => 'nullable|string',
            'time_limit' => 'required|integer|min:1|max:180',
            'word_limit' => 'required|integer|min:50|max:1000',
            'band_level' => ['required', Rule::in(['band6', 'band7', 'band8', 'band9'])],
        ]);

        $task = WritingTask::create([
            'title' => $request->title,
            'task_type' => $request->task_type,
            'prompt' => $request->prompt,
            'instructions' => $request->instructions,
            'time_limit' => $request->time_limit,
            'word_limit' => $request->word_limit,
            'band_level' => $request->band_level,
            'created_by' => Auth::id(),
        ]);
        
        return response()->json([
            'message' => 'Writing task created successfully',
            'task' => $task->load('creator')
        ], 201);
    }

    /**
     * Display the specified writing task
     */
    public function show($id)
    {
        $task = WritingTask::with(['creator'])->findOrFail($id);
        
        return response()->json($task);
    }

    /**
     * Update the specified writing task
     */
    public function update(Request $request, $id)
    {
        $task = WritingTask::findOrFail($id);
        
        // Check if user can update this task
        if ($task->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'task_type' => ['required', Rule::in(['task1', 'task2'])],
            'prompt' => 'required|string',
            'instructions' => 'nullable|string',
            'time_limit' => 'required|integer|min:1|max:180',
            'word_limit' => 'required|integer|min:50|max:1000',
            'band_level' => ['required', Rule::in(['band6', 'band7', 'band8', 'band9'])],
        ]);

        $task->update([
            'title' => $request->title,
            'task_type' => $request->task_type,
            'prompt' => $request->prompt,
            'instructions' => $request->instructions,
            'time_limit' => $request->time_limit,
            'word_limit' => $request->word_limit,
            'band_level' => $request->band_level,
        ]);
        
        return response()->json([
            'message' => 'Writing task updated successfully',
            'task' => $task->fresh()->load('creator')
        ]);
    }

    /**
     * Remove the specified writing task
     */
    public function destroy($id)
    {
        $task = WritingTask::findOrFail($id);
        
        // Check if user can delete this task
        if ($task->created_by !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $task->delete();
        
        return response()->json(['message' => 'Writing task deleted successfully']);
    }
}