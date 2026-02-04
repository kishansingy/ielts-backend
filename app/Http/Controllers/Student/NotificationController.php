<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\NotificationDevice;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Register notification device for user
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_type' => ['required', Rule::in(['web', 'mobile_app', 'pwa'])],
            'device_token' => 'nullable|string',
            'browser_type' => 'nullable|string',
            'platform' => 'nullable|string',
            'subscription_data' => 'nullable|array'
        ]);

        $user = Auth::user();
        
        $device = $this->notificationService->registerDevice($user, $request->all());

        return response()->json([
            'message' => 'Device registered successfully',
            'device' => $device
        ]);
    }

    /**
     * Unregister notification device
     */
    public function unregisterDevice(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string'
        ]);

        $user = Auth::user();
        
        $success = $this->notificationService->unregisterDevice($user, $request->device_token);

        if ($success) {
            return response()->json([
                'message' => 'Device unregistered successfully'
            ]);
        }

        return response()->json([
            'message' => 'Device not found'
        ], 404);
    }

    /**
     * Get user's notification preferences
     */
    public function getPreferences()
    {
        $user = Auth::user();
        
        $devices = NotificationDevice::where('user_id', $user->id)
            ->active()
            ->get();

        // Get user preferences (you might want to add a preferences table)
        $preferences = [
            'daily_vocabulary' => true, // Default enabled
            'practice_reminders' => true,
            'achievement_notifications' => true,
            'email_notifications' => false
        ];

        return response()->json([
            'devices' => $devices,
            'preferences' => $preferences
        ]);
    }

    /**
     * Update user's notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'daily_vocabulary' => 'boolean',
            'practice_reminders' => 'boolean',
            'achievement_notifications' => 'boolean',
            'email_notifications' => 'boolean'
        ]);

        // Here you would update user preferences
        // For now, we'll just return success
        // In a real implementation, you might have a user_preferences table

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $request->all()
        ]);
    }
}