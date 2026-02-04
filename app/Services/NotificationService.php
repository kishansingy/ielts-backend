<?php

namespace App\Services;

use App\Models\User;
use App\Models\VocabularyWord;
use App\Models\NotificationDevice;
use App\Models\UserVocabularyInteraction;
use App\Models\DailyVocabularyNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class NotificationService
{
    /**
     * Send vocabulary notification to a specific device
     */
    public function sendVocabularyNotification(
        VocabularyWord $word, 
        User $user, 
        NotificationDevice $device
    ): bool {
        try {
            $success = match ($device->device_type) {
                'web' => $this->sendWebPushNotification($word, $user, $device),
                'mobile_app' => $this->sendMobileAppNotification($word, $user, $device),
                'pwa' => $this->sendPWANotification($word, $user, $device),
                default => false
            };

            // Log interaction if successful
            if ($success) {
                $this->logUserInteraction($word, $user, 'viewed');
                $device->updateLastUsed();
            }

            return $success;

        } catch (\Exception $e) {
            Log::error("Failed to send vocabulary notification", [
                'word_id' => $word->id,
                'user_id' => $user->id,
                'device_id' => $device->id,
                'device_type' => $device->device_type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send web push notification
     */
    private function sendWebPushNotification(VocabularyWord $word, User $user, NotificationDevice $device): bool
    {
        if (!$device->subscription_data) {
            return false;
        }

        $payload = [
            'title' => '📚 Daily IELTS Vocabulary',
            'body' => "{$word->word}: {$word->meaning}",
            'icon' => '/assets/logo10.png',
            'badge' => '/assets/badge.png',
            'data' => [
                'word_id' => $word->id,
                'word' => $word->word,
                'meaning' => $word->meaning,
                'example' => $word->example_sentence,
                'oxford_url' => $word->oxford_url,
                'url' => '/vocabulary/' . $word->id
            ],
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View Details'
                ],
                [
                    'action' => 'oxford',
                    'title' => 'Oxford Dictionary'
                ]
            ]
        ];

        // Use web push library (you'll need to install web-push-php)
        return $this->sendWebPush($device->subscription_data, $payload);
    }

    /**
     * Send mobile app notification (FCM)
     */
    private function sendMobileAppNotification(VocabularyWord $word, User $user, NotificationDevice $device): bool
    {
        if (!$device->device_token) {
            return false;
        }

        $fcmServerKey = Config::get('services.fcm.server_key');
        if (!$fcmServerKey) {
            Log::warning('FCM server key not configured');
            return false;
        }

        $payload = [
            'to' => $device->device_token,
            'notification' => [
                'title' => '📚 Daily IELTS Vocabulary',
                'body' => "{$word->word}: {$word->meaning}",
                'icon' => 'vocabulary_icon',
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'data' => [
                'type' => 'daily_vocabulary',
                'word_id' => (string) $word->id,
                'word' => $word->word,
                'meaning' => $word->meaning,
                'example' => $word->example_sentence,
                'pronunciation' => $word->pronunciation ?? '',
                'oxford_url' => $word->oxford_url,
                'synonyms' => json_encode($word->synonyms ?? []),
                'antonyms' => json_encode($word->antonyms ?? [])
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $fcmServerKey,
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        return $response->successful();
    }

    /**
     * Send PWA notification
     */
    private function sendPWANotification(VocabularyWord $word, User $user, NotificationDevice $device): bool
    {
        // PWA notifications are similar to web push
        return $this->sendWebPushNotification($word, $user, $device);
    }

    /**
     * Send web push using web-push library
     */
    private function sendWebPush(array $subscription, array $payload): bool
    {
        try {
            // You'll need to install minishlink/web-push package
            // composer require minishlink/web-push
            
            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject' => Config::get('app.url'),
                    'publicKey' => Config::get('services.vapid.public_key'),
                    'privateKey' => Config::get('services.vapid.private_key')
                ]
            ]);

            $result = $webPush->sendOneNotification(
                \Minishlink\WebPush\Subscription::create($subscription),
                json_encode($payload)
            );

            return $result->isSuccess();

        } catch (\Exception $e) {
            Log::error('Web push notification failed', [
                'error' => $e->getMessage(),
                'subscription' => $subscription
            ]);
            return false;
        }
    }

    /**
     * Log user interaction with vocabulary word
     */
    private function logUserInteraction(VocabularyWord $word, User $user, string $interactionType): void
    {
        try {
            UserVocabularyInteraction::updateOrCreate([
                'user_id' => $user->id,
                'vocabulary_word_id' => $word->id,
                'interaction_type' => $interactionType
            ], [
                'interacted_at' => now(),
                'metadata' => [
                    'source' => 'daily_notification',
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log vocabulary interaction', [
                'word_id' => $word->id,
                'user_id' => $user->id,
                'interaction_type' => $interactionType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Register notification device for user
     */
    public function registerDevice(User $user, array $deviceData): NotificationDevice
    {
        return NotificationDevice::updateOrCreate([
            'user_id' => $user->id,
            'device_type' => $deviceData['device_type'],
            'device_token' => $deviceData['device_token'] ?? null
        ], [
            'browser_type' => $deviceData['browser_type'] ?? null,
            'platform' => $deviceData['platform'] ?? null,
            'subscription_data' => $deviceData['subscription_data'] ?? null,
            'is_active' => true,
            'last_used_at' => now()
        ]);
    }

    /**
     * Unregister notification device
     */
    public function unregisterDevice(User $user, string $deviceToken): bool
    {
        return NotificationDevice::where('user_id', $user->id)
            ->where('device_token', $deviceToken)
            ->update(['is_active' => false]);
    }
}