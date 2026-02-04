<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VocabularyWord;
use App\Models\DailyVocabularyNotification;
use App\Models\NotificationDevice;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendDailyVocabularyNotification extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vocabulary:send-daily 
                            {--force : Force send even if already sent today}
                            {--word-id= : Send specific word by ID}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send daily vocabulary notification to all students';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily vocabulary notification process...');

        try {
            // Check if notification already sent today (unless forced)
            if (!$this->option('force') && $this->isAlreadySentToday()) {
                $this->info('Daily vocabulary notification already sent today. Use --force to override.');
                return 0;
            }

            // Get the word to send
            $word = $this->getWordToSend();
            if (!$word) {
                $this->error('No vocabulary word available to send.');
                return 1;
            }

            $this->info("Selected word: {$word->word}");
            $this->info("Meaning: {$word->meaning}");
            $this->info("Example: {$word->example_sentence}");

            if ($this->option('dry-run')) {
                $this->info('DRY RUN: Would send this word to all active students.');
                return 0;
            }

            // Create notification record
            $notification = $this->createNotificationRecord($word);

            // Get all active students
            $students = $this->getActiveStudents();
            $this->info("Found {$students->count()} active students");

            if ($students->isEmpty()) {
                $this->warn('No active students found.');
                $notification->markAsFailed('No active students found');
                return 1;
            }

            // Send notifications
            $results = $this->sendNotifications($word, $students, $notification);

            // Update notification record with results
            $notification->markAsSent(
                $results['total'],
                $results['successful'],
                $results['failed']
            );

            $this->info("Notification process completed:");
            $this->info("- Total recipients: {$results['total']}");
            $this->info("- Successful sends: {$results['successful']}");
            $this->info("- Failed sends: {$results['failed']}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error sending daily vocabulary notification: {$e->getMessage()}");
            Log::error('Daily vocabulary notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Check if notification already sent today
     */
    private function isAlreadySentToday(): bool
    {
        return DailyVocabularyNotification::today()
            ->where('status', 'sent')
            ->exists();
    }

    /**
     * Get word to send today
     */
    private function getWordToSend(): ?VocabularyWord
    {
        // If specific word ID provided
        if ($wordId = $this->option('word-id')) {
            return VocabularyWord::active()->find($wordId);
        }

        // Get highest priority word that hasn't been sent today
        return VocabularyWord::active()
            ->notSentToday()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Create notification record
     */
    private function createNotificationRecord(VocabularyWord $word): DailyVocabularyNotification
    {
        return DailyVocabularyNotification::create([
            'vocabulary_word_id' => $word->id,
            'notification_date' => today(),
            'status' => 'pending',
            'target_audience' => ['all_students'] // For future level-based targeting
        ]);
    }

    /**
     * Get active students
     */
    private function getActiveStudents()
    {
        return User::where('role', 'student')
            ->whereHas('notificationDevices', function ($query) {
                $query->active();
            })
            ->with(['notificationDevices' => function ($query) {
                $query->active();
            }])
            ->get();
    }

    /**
     * Send notifications to all students
     */
    private function sendNotifications(VocabularyWord $word, $students, DailyVocabularyNotification $notification): array
    {
        $results = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0
        ];

        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        foreach ($students as $student) {
            $deviceResults = $this->sendToStudentDevices($word, $student, $notification);
            
            $results['total'] += $deviceResults['total'];
            $results['successful'] += $deviceResults['successful'];
            $results['failed'] += $deviceResults['failed'];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Send notification to all devices of a student
     */
    private function sendToStudentDevices(VocabularyWord $word, User $student, DailyVocabularyNotification $notification): array
    {
        $results = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0
        ];

        foreach ($student->notificationDevices as $device) {
            $results['total']++;

            try {
                $success = $this->notificationService->sendVocabularyNotification($word, $student, $device);
                
                if ($success) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }

            } catch (\Exception $e) {
                $results['failed']++;
                Log::warning("Failed to send vocabulary notification to device {$device->id}", [
                    'user_id' => $student->id,
                    'device_id' => $device->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }
}