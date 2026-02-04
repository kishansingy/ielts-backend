<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VocabularyWord;
use App\Models\DailyVocabularyNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VocabularyController extends Controller
{
    /**
     * Display a listing of vocabulary words
     */
    public function index(Request $request)
    {
        $query = VocabularyWord::query();

        // Filter by difficulty level
        if ($request->has('difficulty_level')) {
            $query->byDifficulty($request->difficulty_level);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by word
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('word', 'like', "%{$search}%")
                  ->orWhere('meaning', 'like', "%{$search}%");
            });
        }

        $words = $query->orderBy('priority', 'desc')
                      ->orderBy('created_at', 'desc')
                      ->paginate(20);

        return response()->json($words);
    }

    /**
     * Store a newly created vocabulary word
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:255|unique:vocabulary_words',
            'meaning' => 'required|string',
            'example_sentence' => 'required|string',
            'pronunciation' => 'nullable|string|max:255',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'word_type' => 'nullable|string|max:100',
            'oxford_url' => 'nullable|url',
            'synonyms' => 'nullable|array',
            'synonyms.*' => 'string|max:255',
            'antonyms' => 'nullable|array',
            'antonyms.*' => 'string|max:255',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean'
        ]);

        $word = VocabularyWord::create($validated);

        return response()->json([
            'message' => 'Vocabulary word created successfully',
            'word' => $word
        ], 201);
    }

    /**
     * Display the specified vocabulary word
     */
    public function show(VocabularyWord $vocabularyWord)
    {
        $vocabularyWord->load(['dailyNotifications', 'userInteractions']);
        
        return response()->json($vocabularyWord);
    }

    /**
     * Update the specified vocabulary word
     */
    public function update(Request $request, VocabularyWord $vocabularyWord)
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:255', Rule::unique('vocabulary_words')->ignore($vocabularyWord->id)],
            'meaning' => 'required|string',
            'example_sentence' => 'required|string',
            'pronunciation' => 'nullable|string|max:255',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'word_type' => 'nullable|string|max:100',
            'oxford_url' => 'nullable|url',
            'synonyms' => 'nullable|array',
            'synonyms.*' => 'string|max:255',
            'antonyms' => 'nullable|array',
            'antonyms.*' => 'string|max:255',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean'
        ]);

        $vocabularyWord->update($validated);

        return response()->json([
            'message' => 'Vocabulary word updated successfully',
            'word' => $vocabularyWord
        ]);
    }

    /**
     * Remove the specified vocabulary word
     */
    public function destroy(VocabularyWord $vocabularyWord)
    {
        // Check if word has been sent in notifications
        $hasNotifications = $vocabularyWord->dailyNotifications()->exists();
        
        if ($hasNotifications) {
            // Soft delete by marking as inactive instead of actual deletion
            $vocabularyWord->update(['is_active' => false]);
            
            return response()->json([
                'message' => 'Vocabulary word deactivated (has notification history)'
            ]);
        }

        $vocabularyWord->delete();

        return response()->json([
            'message' => 'Vocabulary word deleted successfully'
        ]);
    }

    /**
     * Bulk import vocabulary words
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'words' => 'required|array|min:1',
            'words.*.word' => 'required|string|max:255',
            'words.*.meaning' => 'required|string',
            'words.*.example_sentence' => 'required|string',
            'words.*.pronunciation' => 'nullable|string|max:255',
            'words.*.difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'words.*.word_type' => 'nullable|string|max:100',
            'words.*.oxford_url' => 'nullable|url',
            'words.*.synonyms' => 'nullable|array',
            'words.*.antonyms' => 'nullable|array',
            'words.*.priority' => 'nullable|integer|min:0|max:100'
        ]);

        $imported = 0;
        $errors = [];

        foreach ($request->words as $index => $wordData) {
            try {
                // Check if word already exists
                if (VocabularyWord::where('word', $wordData['word'])->exists()) {
                    $errors[] = "Row {$index}: Word '{$wordData['word']}' already exists";
                    continue;
                }

                VocabularyWord::create($wordData);
                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Row {$index}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'message' => "Import completed. {$imported} words imported.",
            'imported_count' => $imported,
            'errors' => $errors
        ]);
    }

    /**
     * Get notification history
     */
    public function notificationHistory(Request $request)
    {
        $query = DailyVocabularyNotification::with('vocabularyWord');

        if ($request->has('date_from')) {
            $query->where('notification_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('notification_date', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->orderBy('notification_date', 'desc')
                              ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request, VocabularyWord $vocabularyWord)
    {
        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        // This would trigger a test notification
        // Implementation depends on your notification service
        
        return response()->json([
            'message' => 'Test notification queued successfully'
        ]);
    }
}