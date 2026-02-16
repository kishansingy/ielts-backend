<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $provider;
    
    public function __construct()
    {
        $this->provider = env('SMS_PROVIDER', 'cache');
    }

    /**
     * Send OTP via SMS
     */
    public function sendOtp(string $phoneNumber): array
    {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in cache for 5 minutes
        Cache::put('otp_' . $phoneNumber, $otp, now()->addMinutes(5));
        
        Log::info('OTP generated for ' . $phoneNumber . ': ' . $otp);
        
        // Send SMS based on provider
        $result = $this->sendSms($phoneNumber, $otp);
        
        // Return OTP in debug mode only
        if (env('APP_DEBUG')) {
            $result['otp'] = $otp;
        }
        
        return $result;
    }

    /**
     * Send SMS using configured provider
     */
    private function sendSms(string $phoneNumber, string $otp): array
    {
        try {
            switch ($this->provider) {
                case 'msg91':
                    return $this->sendViaMSG91($phoneNumber, $otp);
                    
                case 'twilio':
                    return $this->sendViaTwilio($phoneNumber, $otp);
                    
                case 'fast2sms':
                    return $this->sendViaFast2SMS($phoneNumber, $otp);
                    
                case 'aws':
                    return $this->sendViaAWS($phoneNumber, $otp);
                    
                case 'cache':
                default:
                    return $this->sendViaCache($phoneNumber, $otp);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send via MSG91 (Popular in India)
     */
    private function sendViaMSG91(string $phoneNumber, string $otp): array
    {
        $authKey = env('MSG91_AUTH_KEY');
        $senderId = env('MSG91_SENDER_ID', 'LRNIEL');
        $templateId = env('MSG91_TEMPLATE_ID');
        $route = env('MSG91_ROUTE', '4'); // 4 = Transactional
        
        if (!$authKey) {
            throw new \Exception('MSG91_AUTH_KEY not configured');
        }
        
        // Format phone number (remove +91 if present)
        $mobile = preg_replace('/^\+91/', '', $phoneNumber);
        
        $message = "Your OTP for LearnIELTS is {$otp}. Valid for 5 minutes. Do not share with anyone.";
        
        $url = "https://api.msg91.com/api/v5/flow/";
        
        $data = [
            'template_id' => $templateId,
            'sender' => $senderId,
            'short_url' => '0',
            'mobiles' => '91' . $mobile,
            'var1' => $otp,
            'route' => $route
        ];
        
        $response = Http::withHeaders([
            'authkey' => $authKey,
            'content-type' => 'application/json'
        ])->post($url, $data);
        
        if ($response->successful()) {
            Log::info('MSG91 SMS sent successfully to ' . $phoneNumber);
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'provider' => 'msg91'
            ];
        }
        
        Log::error('MSG91 SMS failed: ' . $response->body());
        throw new \Exception('MSG91 API error: ' . $response->body());
    }

    /**
     * Send via Twilio (International)
     */
    private function sendViaTwilio(string $phoneNumber, string $otp): array
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_FROM_NUMBER');
        
        if (!$sid || !$token || !$from) {
            throw new \Exception('Twilio credentials not configured');
        }
        
        // Ensure phone number has country code
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+91' . $phoneNumber;
        }
        
        $message = "Your OTP for LearnIELTS is {$otp}. Valid for 5 minutes.";
        
        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $phoneNumber,
                'Body' => $message
            ]);
        
        if ($response->successful()) {
            Log::info('Twilio SMS sent successfully to ' . $phoneNumber);
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'provider' => 'twilio'
            ];
        }
        
        Log::error('Twilio SMS failed: ' . $response->body());
        throw new \Exception('Twilio API error: ' . $response->body());
    }

    /**
     * Send via Fast2SMS (Budget option for India)
     */
    private function sendViaFast2SMS(string $phoneNumber, string $otp): array
    {
        $apiKey = env('FAST2SMS_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('FAST2SMS_API_KEY not configured');
        }
        
        // Format phone number (remove +91 if present)
        $mobile = preg_replace('/^\+91/', '', $phoneNumber);
        
        $message = "Your OTP for LearnIELTS is {$otp}. Valid for 5 minutes.";
        
        $response = Http::withHeaders([
            'authorization' => $apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://www.fast2sms.com/dev/bulkV2', [
            'route' => 'v3',
            'sender_id' => 'LRNIEL',
            'message' => $message,
            'language' => 'english',
            'flash' => 0,
            'numbers' => $mobile
        ]);
        
        if ($response->successful()) {
            Log::info('Fast2SMS sent successfully to ' . $phoneNumber);
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'provider' => 'fast2sms'
            ];
        }
        
        Log::error('Fast2SMS failed: ' . $response->body());
        throw new \Exception('Fast2SMS API error: ' . $response->body());
    }

    /**
     * Send via AWS SNS
     */
    private function sendViaAWS(string $phoneNumber, string $otp): array
    {
        // Ensure phone number has country code
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+91' . $phoneNumber;
        }
        
        $message = "Your OTP for LearnIELTS is {$otp}. Valid for 5 minutes.";
        
        // You'll need to install AWS SDK: composer require aws/aws-sdk-php
        $sns = new \Aws\Sns\SnsClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        $result = $sns->publish([
            'Message' => $message,
            'PhoneNumber' => $phoneNumber,
        ]);
        
        if ($result['@metadata']['statusCode'] == 200) {
            Log::info('AWS SNS sent successfully to ' . $phoneNumber);
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'provider' => 'aws'
            ];
        }
        
        throw new \Exception('AWS SNS error');
    }

    /**
     * Cache-based OTP (for development/testing)
     */
    private function sendViaCache(string $phoneNumber, string $otp): array
    {
        Log::info('Cache-based OTP (no SMS sent) for ' . $phoneNumber . ': ' . $otp);
        
        return [
            'success' => true,
            'message' => 'OTP generated (development mode - no SMS sent)',
            'provider' => 'cache'
        ];
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $phoneNumber, string $otp, bool $clearAfterVerify = false): array
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
        
        if ($clearAfterVerify) {
            Cache::forget('otp_' . $phoneNumber);
        }
        
        return [
            'success' => true,
            'message' => 'OTP verified successfully'
        ];
    }
}
