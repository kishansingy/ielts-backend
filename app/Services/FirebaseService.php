<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    private $apiKey;
    
    public function __construct()
    {
        $this->apiKey = env('FIREBASE_WEB_API_KEY');
    }

    /**
     * Send OTP via Firebase Phone Authentication
     * Note: This requires Firebase Phone Auth to be enabled in Firebase Console
     */
    public function sendOtp(string $phoneNumber)
    {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in cache for 5 minutes
        Cache::put('otp_' . $phoneNumber, $otp, now()->addMinutes(5));
        
        // In production with Firebase Phone Auth:
        // You would use Firebase Admin SDK or REST API to send SMS
        // For now, we'll use cache-based OTP for development
        
        \Log::info('OTP generated for ' . $phoneNumber . ': ' . $otp);
        
        return [
            'success' => true,
            'message' => 'OTP sent successfully',
            'otp' => env('APP_DEBUG') ? $otp : null // Only return OTP in debug mode
        ];
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $phoneNumber, string $otp, bool $clearAfterVerify = false)
    {
        $cachedOtp = Cache::get('otp_' . $phoneNumber);
        
        if (!$cachedOtp) {
            return [
                'success' => false,
                'message' => 'OTP expired or not found'
            ];
        }
        
        if ($cachedOtp != $otp) {
            return [
                'success' => false,
                'message' => 'Invalid OTP'
            ];
        }
        
        // Only clear OTP if explicitly requested (e.g., during login)
        if ($clearAfterVerify) {
            Cache::forget('otp_' . $phoneNumber);
        }
        
        return [
            'success' => true,
            'message' => 'OTP verified successfully'
        ];
    }

    /**
     * Verify Firebase ID Token (for web/mobile app authentication)
     * This is used when the client handles Firebase Auth directly
     */
    public function verifyIdToken(string $idToken)
    {
        try {
            // Verify the ID token using Firebase REST API
            $response = Http::post('https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . $this->apiKey, [
                'idToken' => $idToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['users'][0])) {
                    return [
                        'success' => true,
                        'user' => $data['users'][0]
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Invalid token'
            ];
        } catch (\Exception $e) {
            \Log::error('Firebase token verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Token verification failed'
            ];
        }
    }
}
