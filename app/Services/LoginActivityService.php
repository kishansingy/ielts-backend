<?php

namespace App\Services;

use App\Models\LoginActivity;
use Illuminate\Http\Request;

class LoginActivityService
{
    /**
     * Log a login attempt
     */
    public function logLoginAttempt(Request $request, $user = null, $status = 'failed', $failureReason = null)
    {
        $deviceType = $this->detectDeviceType($request);
        
        $data = [
            'user_id' => $user ? $user->id : null,
            'login_type' => $request->input('login_type', 'email'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'status' => $status,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceType,
            'failure_reason' => $failureReason,
        ];

        if ($status === 'success') {
            $data['logged_in_at'] = now();
        }

        return LoginActivity::create($data);
    }

    /**
     * Log a successful login
     */
    public function logSuccessfulLogin(Request $request, $user)
    {
        return $this->logLoginAttempt($request, $user, 'success');
    }

    /**
     * Log a failed login
     */
    public function logFailedLogin(Request $request, $failureReason)
    {
        return $this->logLoginAttempt($request, null, 'failed', $failureReason);
    }

    /**
     * Log a logout
     */
    public function logLogout(Request $request, $user)
    {
        // Find the most recent successful login for this user
        $lastLogin = LoginActivity::where('user_id', $user->id)
            ->where('status', 'success')
            ->whereNull('logged_out_at')
            ->latest('logged_in_at')
            ->first();

        if ($lastLogin) {
            $lastLogin->update([
                'logged_out_at' => now(),
            ]);
        }

        // Also create a logout record
        return LoginActivity::create([
            'user_id' => $user->id,
            'login_type' => $user->email ? 'email' : 'mobile',
            'email' => $user->email,
            'mobile' => $user->mobile,
            'status' => 'logout',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->detectDeviceType($request),
            'logged_out_at' => now(),
        ]);
    }

    /**
     * Detect device type from request
     */
    private function detectDeviceType(Request $request)
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        // Check if it's a mobile app (Capacitor/Ionic)
        if (strpos($userAgent, 'capacitor') !== false || 
            strpos($userAgent, 'ionic') !== false) {
            return 'mobile';
        }
        
        // Check for mobile browsers
        if (preg_match('/(android|iphone|ipad|mobile)/i', $userAgent)) {
            return 'mobile';
        }
        
        return 'web';
    }

    /**
     * Get user's login history
     */
    public function getUserLoginHistory($userId, $limit = 10)
    {
        return LoginActivity::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed login attempts for a user
     */
    public function getFailedLoginAttempts($identifier, $minutes = 15)
    {
        $query = LoginActivity::where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($minutes));

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $query->where('email', $identifier);
        } else {
            $query->where('mobile', $identifier);
        }

        return $query->count();
    }
}
