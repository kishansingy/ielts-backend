<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyVocabularyNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'vocabulary_word_id',
        'notification_date',
        'status',
        'target_audience',
        'total_recipients',
        'successful_sends',
        'failed_sends',
        'failure_reason',
        'sent_at'
    ];

    protected $casts = [
        'notification_date' => 'date',
        'target_audience' => 'array',
        'sent_at' => 'datetime'
    ];

    /**
     * Get the vocabulary word
     */
    public function vocabularyWord(): BelongsTo
    {
        return $this->belongsTo(VocabularyWord::class);
    }

    /**
     * Get user interactions for this notification
     */
    public function userInteractions(): HasMany
    {
        return $this->hasMany(UserVocabularyInteraction::class);
    }

    /**
     * Scope for today's notifications
     */
    public function scopeToday($query)
    {
        return $query->where('notification_date', today());
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Mark as sent
     */
    public function markAsSent($totalRecipients = 0, $successfulSends = 0, $failedSends = 0)
    {
        $this->update([
            'status' => 'sent',
            'total_recipients' => $totalRecipients,
            'successful_sends' => $successfulSends,
            'failed_sends' => $failedSends,
            'sent_at' => now()
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason
        ]);
    }
}