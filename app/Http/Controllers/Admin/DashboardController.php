<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attempt;
use App\Models\Submission;
use App\Models\ReadingPassage;
use App\Models\WritingTask;
use App\Models\ListeningExercise;
use App\Models\SpeakingPrompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard overview
     */
    public function index()
    {
        $dashboardData = [
            'overview_stats' => $this->getOverviewStats(),
            'user_analytics' => $this->getUserAnalytics(),
            'content_analytics' => $this->getContentAnalytics(),
            'recent_activity' => $this->getRecentActivity(),
            'performance_metrics' => $this->getPerformanceMetrics(),
        ];
        
        return response()->json($dashboardData);
    }

    /**
     * Get detailed user analytics
     */
    public function userAnalytics(Request $request)
    {
        $period = $request->get('period', 30); // days
        $startDate = Carbon::now()->subDays($period);
        
        return response()->json([
            'user_registration_trends' => $this->getUserRegistrationTrends($period),
            'active_users' => $this->getActiveUsers($period),
            'user_engagement' => $this->getUserEngagement($period),
            'top_performers' => $this->getTopPerformers(),
        ]);
    }

    /**
     * Get content usage analytics
     */
    public function contentAnalytics(Request $request)
    {
        $period = $request->get('period', 30);
        
        return response()->json([
            'content_usage' => $this->getContentUsage($period),
            'popular_content' => $this->getPopularContent($period),
            'content_performance' => $this->getContentPerformance($period),
        ]);
    }

    /**
     * Get system performance metrics
     */
    public function performanceMetrics(Request $request)
    {
        $period = $request->get('period', 7);
        
        return response()->json([
            'daily_activity' => $this->getDailyActivity($period),
            'module_distribution' => $this->getModuleDistribution($period),
            'score_distribution' => $this->getScoreDistribution($period),
        ]);
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats()
    {
        $totalUsers = User::students()->count();
        $totalAdmins = User::admins()->count();
        $totalAttempts = Attempt::completed()->count();
        $totalSubmissions = Submission::count();
        
        // Content counts
        $contentCounts = [
            'reading_passages' => ReadingPassage::count(),
            'writing_tasks' => WritingTask::count(),
            'listening_exercises' => ListeningExercise::count(),
            'speaking_prompts' => SpeakingPrompt::count(),
        ];
        
        // Recent activity (last 7 days)
        $recentAttempts = Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays(7))
            ->count();
            
        $recentSubmissions = Submission::where('submitted_at', '>=', Carbon::now()->subDays(7))
            ->count();
        
        return [
            'total_students' => $totalUsers,
            'total_admins' => $totalAdmins,
            'total_attempts' => $totalAttempts,
            'total_submissions' => $totalSubmissions,
            'content_counts' => $contentCounts,
            'recent_activity' => [
                'attempts_last_7_days' => $recentAttempts,
                'submissions_last_7_days' => $recentSubmissions,
            ],
        ];
    }

    /**
     * Get user analytics
     */
    private function getUserAnalytics()
    {
        $activeUsers = User::students()
            ->whereHas('attempts', function($query) {
                $query->where('completed_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();
            
        $averageScore = Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->avg('score');
            
        return [
            'active_users_30_days' => $activeUsers,
            'average_score_30_days' => round($averageScore ?? 0, 2),
            'user_retention_rate' => $this->calculateRetentionRate(),
        ];
    }

    /**
     * Get content analytics
     */
    private function getContentAnalytics()
    {
        $moduleUsage = Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->select('module_type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('module_type')
            ->pluck('usage_count', 'module_type');
            
        return [
            'module_usage' => $moduleUsage,
            'most_popular_module' => $moduleUsage->keys()->first(),
            'content_creation_rate' => $this->getContentCreationRate(),
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($limit = 20)
    {
        $recentAttempts = Attempt::with(['user', 'content'])
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
            
        return $recentAttempts->map(function ($attempt) {
            return [
                'type' => 'attempt',
                'user_name' => $attempt->user->name,
                'module' => $attempt->module_type,
                'score' => $attempt->score,
                'percentage' => $attempt->percentage,
                'completed_at' => $attempt->completed_at,
                'content_title' => $attempt->content->title ?? 'Unknown',
            ];
        });
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics()
    {
        $modulePerformance = [];
        $modules = ['reading', 'writing', 'listening', 'speaking'];
        
        foreach ($modules as $module) {
            $attempts = Attempt::byModule($module)
                ->completed()
                ->where('completed_at', '>=', Carbon::now()->subDays(30));
                
            $modulePerformance[$module] = [
                'total_attempts' => $attempts->count(),
                'average_score' => round($attempts->avg('score') ?? 0, 2),
                'completion_rate' => $this->getModuleCompletionRate($module),
            ];
        }
        
        return $modulePerformance;
    }

    /**
     * Get user registration trends
     */
    private function getUserRegistrationTrends($days)
    {
        return User::students()
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as registrations'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get active users
     */
    private function getActiveUsers($days)
    {
        return User::students()
            ->whereHas('attempts', function($query) use ($days) {
                $query->where('completed_at', '>=', Carbon::now()->subDays($days));
            })
            ->count();
    }

    /**
     * Get user engagement metrics
     */
    private function getUserEngagement($days)
    {
        $totalUsers = User::students()->count();
        $activeUsers = $this->getActiveUsers($days);
        
        $avgAttemptsPerUser = Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays($days))
            ->count() / max($activeUsers, 1);
            
        return [
            'engagement_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
            'avg_attempts_per_user' => round($avgAttemptsPerUser, 2),
        ];
    }

    /**
     * Get top performers
     */
    private function getTopPerformers($limit = 10)
    {
        return User::students()
            ->select('users.*')
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('users.id')
            ->selectRaw('users.*, AVG(attempts.score) as average_score, COUNT(attempts.id) as total_attempts')
            ->orderByDesc('average_score')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'average_score' => round($user->average_score, 2),
                    'total_attempts' => $user->total_attempts,
                ];
            });
    }

    /**
     * Calculate retention rate
     */
    private function calculateRetentionRate()
    {
        $usersLastMonth = User::students()
            ->where('created_at', '<=', Carbon::now()->subDays(30))
            ->count();
            
        $activeUsersFromLastMonth = User::students()
            ->where('created_at', '<=', Carbon::now()->subDays(30))
            ->whereHas('attempts', function($query) {
                $query->where('completed_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();
            
        return $usersLastMonth > 0 ? round(($activeUsersFromLastMonth / $usersLastMonth) * 100, 2) : 0;
    }

    /**
     * Get content creation rate
     */
    private function getContentCreationRate()
    {
        $contentCreated = collect([
            ReadingPassage::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            WritingTask::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            ListeningExercise::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
            SpeakingPrompt::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
        ])->sum();
        
        return $contentCreated;
    }

    /**
     * Get module completion rate
     */
    private function getModuleCompletionRate($module)
    {
        $totalAttempts = Attempt::byModule($module)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();
            
        $completedAttempts = Attempt::byModule($module)
            ->completed()
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->count();
            
        return $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 2) : 0;
    }

    /**
     * Get daily activity
     */
    private function getDailyActivity($days)
    {
        return Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays($days))
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as attempts'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get module distribution
     */
    private function getModuleDistribution($days)
    {
        return Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays($days))
            ->select('module_type', DB::raw('COUNT(*) as count'))
            ->groupBy('module_type')
            ->pluck('count', 'module_type');
    }

    /**
     * Get score distribution
     */
    private function getScoreDistribution($days)
    {
        $scores = Attempt::completed()
            ->where('completed_at', '>=', Carbon::now()->subDays($days))
            ->pluck('score');
            
        $distribution = [
            '0-20' => $scores->filter(fn($s) => $s >= 0 && $s < 20)->count(),
            '20-40' => $scores->filter(fn($s) => $s >= 20 && $s < 40)->count(),
            '40-60' => $scores->filter(fn($s) => $s >= 40 && $s < 60)->count(),
            '60-80' => $scores->filter(fn($s) => $s >= 60 && $s < 80)->count(),
            '80-100' => $scores->filter(fn($s) => $s >= 80 && $s <= 100)->count(),
        ];
        
        return $distribution;
    }
}