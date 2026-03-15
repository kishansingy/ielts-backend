<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenerationSetting;
use App\Models\GeminiUsageTracking;
use Illuminate\Http\Request;

class AiSettingsController extends Controller
{
    /**
     * Get all AI generation settings
     */
    public function index()
    {
        $settings = AiGenerationSetting::all();
        
        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    /**
     * Update a setting
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'value' => 'required'
        ]);

        $setting = AiGenerationSetting::where('key', $key)->firstOrFail();
        
        $value = $request->value;
        if ($setting->type === 'json' && is_string($value)) {
            $value = json_decode($value, true);
        }

        AiGenerationSetting::set($key, $value, $setting->type);

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully'
        ]);
    }

    /**
     * Get usage dashboard data
     */
    public function dashboard()
    {
        $settings = AiGenerationSetting::getAll();
        $limitStatus = GeminiUsageTracking::isApproachingDailyLimit(
            $settings['daily_request_limit'] ?? 750
        );
        $todayStats = GeminiUsageTracking::getDailyStats();
        
        // Calculate estimated capacity
        $avgTokensPerRequest = 1475; // Based on current usage
        $dailyRequestLimit = $settings['daily_request_limit'] ?? 750;
        
        $capacity = [
            'reading' => floor($dailyRequestLimit / 4), // 4 requests per passage
            'writing' => floor($dailyRequestLimit / 2), // 2 requests per task
            'speaking' => $dailyRequestLimit, // 1 request per set
            'listening' => floor($dailyRequestLimit / 2), // 2 requests per exercise
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'usage' => [
                    'today' => $todayStats,
                    'limit_status' => $limitStatus
                ],
                'capacity' => $capacity,
                'recommendations' => $this->getRecommendations($limitStatus, $settings)
            ]
        ]);
    }

    /**
     * Toggle daily generation on/off
     */
    public function toggleDailyGeneration(Request $request)
    {
        $enabled = $request->input('enabled', true);
        
        AiGenerationSetting::set('daily_generation_enabled', $enabled, 'boolean');

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Daily generation enabled' : 'Daily generation disabled',
            'enabled' => $enabled
        ]);
    }

    /**
     * Update generation schedule
     */
    public function updateSchedule(Request $request)
    {
        $request->validate([
            'time' => 'required|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'modules' => 'required|array',
            'band_levels' => 'required|array',
            'count_per_module' => 'required|array'
        ]);

        AiGenerationSetting::set('daily_generation_time', $request->time, 'string');
        AiGenerationSetting::set('enabled_modules', $request->modules, 'json');
        AiGenerationSetting::set('enabled_band_levels', $request->band_levels, 'json');
        AiGenerationSetting::set('generation_per_module', $request->count_per_module, 'json');

        return response()->json([
            'success' => true,
            'message' => 'Generation schedule updated successfully'
        ]);
    }

    /**
     * Get recommendations based on usage
     */
    private function getRecommendations($limitStatus, $settings)
    {
        $recommendations = [];

        if ($limitStatus['approaching_limit']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Approaching daily request limit. Consider reducing generation count or disabling some modules.'
            ];
        }

        if ($limitStatus['percentage'] < 50) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'You have plenty of capacity. Consider increasing generation count for better content variety.'
            ];
        }

        return $recommendations;
    }
}
