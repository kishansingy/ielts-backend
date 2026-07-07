<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ProgressTrackingService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $progressService;
    protected $leaderboardService;

    public function __construct(ProgressTrackingService $progressService, LeaderboardService $leaderboardService)
    {
        $this->progressService = $progressService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Get student dashboard data
     */
    public function index()
    {
        $userId = Auth::id();
        
        $dashboardData = [
            'progress' => $this->progressService->getUserProgress($userId),
            'leaderboard_position' => [
                'overall' => $this->leaderboardService->getUserPosition($userId, 'overall'),
                'weekly' => $this->leaderboardService->getUserPosition($userId, 'weekly'),
            ],
            'quick_stats' => $this->getQuickStats($userId),
        ];
        
        return response()->json($dashboardData);
    }

    /**
     * Get user progress details
     */
    public function progress()
    {
        $userId = Auth::id();
        $progress = $this->progressService->getUserProgress($userId);
        
        return response()->json($progress);
    }

    /**
     * Get leaderboard data
     */
    public function leaderboard(Request $request)
    {
        $type = $request->get('type', 'overall'); // overall, weekly, module
        $module = $request->get('module');
        $limit = $request->get('limit', 10);
        
        switch ($type) {
            case 'weekly':
                $leaderboard = $this->leaderboardService->getWeeklyLeaderboard($limit);
                break;
            case 'module':
                if (!$module) {
                    return response()->json(['error' => 'Module parameter required for module leaderboard'], 400);
                }
                $leaderboard = $this->leaderboardService->getModuleLeaderboard($module, $limit);
                break;
            default:
                $leaderboard = $this->leaderboardService->getOverallLeaderboard($limit);
        }
        
        $userPosition = $this->leaderboardService->getUserPosition(Auth::id(), $type, $module);
        
        return response()->json([
            'leaderboard' => $leaderboard,
            'user_position' => $userPosition,
            'type' => $type,
            'module' => $module,
        ]);
    }

    /**
     * Get performance trends
     */
    public function trends(Request $request)
    {
        $userId = Auth::id();
        $days = $request->get('days', 30);
        
        $trends = $this->progressService->getPerformanceTrends($userId, $days);
        
        return response()->json($trends);
    }

    /**
     * Get user achievements
     */
    public function achievements()
    {
        $userId = Auth::id();
        $achievements = $this->progressService->getUserAchievements($userId);
        
        return response()->json($achievements);
    }

    /**
     * Get quick stats for dashboard overview
     */
    private function getQuickStats($userId)
    {
        $overallStats = $this->progressService->getOverallStats($userId);
        $moduleBreakdown = $this->progressService->getModuleBreakdown($userId);
        
        // Find best performing module
        $bestModule = collect($moduleBreakdown)->sortByDesc('average_score')->keys()->first();
        
        // Find most practiced module
        $mostPracticedModule = collect($moduleBreakdown)->sortByDesc('attempts_count')->keys()->first();
        
        return [
            'total_practice_time' => $overallStats['total_time_spent'],
            'current_streak' => $overallStats['streak_days'],
            'best_module' => [
                'name' => $bestModule,
                'score' => $moduleBreakdown[$bestModule]['average_score'] ?? 0,
            ],
            'most_practiced_module' => [
                'name' => $mostPracticedModule,
                'attempts' => $moduleBreakdown[$mostPracticedModule]['attempts_count'] ?? 0,
            ],
            'recent_improvement' => $this->calculateRecentImprovement($userId),
        ];
    }

    /**
     * Calculate recent improvement percentage
     */
    private function calculateRecentImprovement($userId)
    {
        $recentAttempts = \App\Models\Attempt::byUser($userId)
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->pluck('score');
            
        if ($recentAttempts->count() < 5) {
            return 0;
        }
        
        $recentAvg = $recentAttempts->take(5)->avg();
        $olderAvg = $recentAttempts->skip(5)->avg();
        
        if ($olderAvg == 0) {
            return $recentAvg > 0 ? 100 : 0;
        }
        
        return round((($recentAvg - $olderAvg) / $olderAvg) * 100, 2);
    }
}