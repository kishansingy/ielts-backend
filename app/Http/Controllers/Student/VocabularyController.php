<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\VocabularyWord;
use App\Models\UserVocabularyInteraction;
use App\Models\DailyVocabularyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VocabularyController extends Controller
{
    /**
     * Get today's daily vocabulary word
     */
    public function getDailyWord()
    {
        $today = today();
        
        // Get today's notification
        $notification = DailyVocabularyNotification::today()
            ->where('status', 'sent')
            ->with('vocabularyWord')
            ->first();

        if (!$notification || !$notification->vocabularyWord) {
            return response()->json([
                'message' => 'No daily word available today',
                'word' => null
            ]);
        }

        $word = $notification->vocabularyWord;
        $userId = Auth::id();

        // Check if user has interacted with this word
        $userInteraction = UserVocabularyInteraction::where('user_id', $userId)
            ->where('vocabulary_word_id', $word->id)
            ->where('daily_vocabulary_notification_id', $notification->id)
            ->first();

        // Mark as viewed if not already
        if (!$userInteraction) {
            UserVocabularyInteraction::create([
                'user_id' => $userId,
                'vocabulary_word_id' => $word->id,
                'daily_vocabulary_notification_id' => $notification->id,
                'interaction_type' => 'viewed',
                'interacted_at' => now(),
                'metadata' => [
                    'source' => 'daily_word_api',
                    'timestamp' => now()->toISOString()
                ]
            ]);
        }

        // Get user's bookmark status
        $isBookmarked = UserVocabularyInteraction::where('user_id', $userId)
            ->where('vocabulary_word_id', $word->id)
            ->where('interaction_type', 'bookmarked')
            ->exists();

        return response()->json([
            'word' => $word,
            'notification_date' => $notification->notification_date,
            'is_bookmarked' => $isBookmarked,
            'user_interaction' => $userInteraction
        ]);
    }

    /**
     * Get user's vocabulary history
     */
    public function getHistory(Request $request)
    {
        $userId = Auth::id();
        
        $query = UserVocabularyInteraction::where('user_id', $userId)
            ->with(['vocabularyWord', 'dailyVocabularyNotification'])
            ->orderBy('interacted_at', 'desc');

        // Filter by interaction type
        if ($request->has('type')) {
            $query->where('interaction_type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('interacted_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('interacted_at', '<=', $request->date_to);
        }

        $interactions = $query->paginate(20);

        return response()->json($interactions);
    }

    /**
     * Show specific vocabulary word
     */
    public function show(VocabularyWord $word)
    {
        $userId = Auth::id();

        // Get user's interactions with this word
        $interactions = UserVocabularyInteraction::where('user_id', $userId)
            ->where('vocabulary_word_id', $word->id)
            ->orderBy('interacted_at', 'desc')
            ->get();

        // Check if bookmarked
        $isBookmarked = $interactions->where('interaction_type', 'bookmarked')->isNotEmpty();

        // Mark as viewed
        UserVocabularyInteraction::updateOrCreate([
            'user_id' => $userId,
            'vocabulary_word_id' => $word->id,
            'interaction_type' => 'viewed'
        ], [
            'interacted_at' => now(),
            'metadata' => [
                'source' => 'word_detail_view',
                'timestamp' => now()->toISOString()
            ]
        ]);

        return response()->json([
            'word' => $word,
            'is_bookmarked' => $isBookmarked,
            'interactions' => $interactions
        ]);
    }

    /**
     * Record user interaction with vocabulary word
     */
    public function recordInteraction(Request $request, VocabularyWord $word)
    {
        $request->validate([
            'interaction_type' => 'required|in:viewed,practiced,mastered',
            'metadata' => 'nullable|array'
        ]);

        $userId = Auth::id();

        $interaction = UserVocabularyInteraction::updateOrCreate([
            'user_id' => $userId,
            'vocabulary_word_id' => $word->id,
            'interaction_type' => $request->interaction_type
        ], [
            'interacted_at' => now(),
            'metadata' => array_merge($request->metadata ?? [], [
                'timestamp' => now()->toISOString()
            ])
        ]);

        return response()->json([
            'message' => 'Interaction recorded successfully',
            'interaction' => $interaction
        ]);
    }

    /**
     * Bookmark vocabulary word
     */
    public function bookmark(VocabularyWord $word)
    {
        $userId = Auth::id();

        $interaction = UserVocabularyInteraction::updateOrCreate([
            'user_id' => $userId,
            'vocabulary_word_id' => $word->id,
            'interaction_type' => 'bookmarked'
        ], [
            'interacted_at' => now(),
            'metadata' => [
                'source' => 'bookmark_action',
                'timestamp' => now()->toISOString()
            ]
        ]);

        return response()->json([
            'message' => 'Word bookmarked successfully',
            'interaction' => $interaction
        ]);
    }

    /**
     * Remove bookmark from vocabulary word
     */
    public function removeBookmark(VocabularyWord $word)
    {
        $userId = Auth::id();

        $deleted = UserVocabularyInteraction::where('user_id', $userId)
            ->where('vocabulary_word_id', $word->id)
            ->where('interaction_type', 'bookmarked')
            ->delete();

        if ($deleted) {
            return response()->json([
                'message' => 'Bookmark removed successfully'
            ]);
        }

        return response()->json([
            'message' => 'Bookmark not found'
        ], 404);
    }
}