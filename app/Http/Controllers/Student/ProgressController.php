<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\User;
use App\Services\ProgressTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProgressController extends Controller
{
    protected $progressService;

    public function __construct(ProgressTrackingService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Get student's progress dashboard data
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get overall statistics
        $overallStats = $this->getOverallStats($user);
        
        // Get module-specific statistics
        $moduleStats = $this->getModuleStats($user);
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity($user);
        
        return response()->json([
            'overall' => $overallStats,
            'modules' => $moduleStats,
            'recent_activity' => $recentActivity
        ]);
    }

    /**
     * Get leaderboard data
     */
    public function leaderboard(Request $request)
    {
        $module = $request->get('module', 'overall');
        $period = $request->get('period', 'week');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = $this->buildLeaderboardQuery($module, $period);
        
        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $leaderboard = $query->offset($offset)->limit($perPage + 1)->get();
        
        $hasMore = $leaderboard->count() > $perPage;
        if ($hasMore) {
            $leaderboard = $leaderboard->take($perPage);
        }
        
        // Get user's rank
        $userRank = $this->getUserRank(Auth::id(), $module, $period);
        
        return response()->json([
            'data' => $leaderboard,
            'user_rank' => $userRank,
            'has_more' => $hasMore
        ]);
    }

    /**
     * Get detailed progress for a specific module
     */
    public function moduleProgress($module)
    {
        $user = Auth::user();
        
        $attempts = Attempt::where('user_id', $user->id)
            ->where('module_type', $module)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $stats = $this->progressService->getModuleStats($user->id, $module);
        
        return response()->json([
            'attempts' => $attempts,
            'stats' => $stats
        ]);
    }

    /**
     * Get progress chart data
     */
    public function chartData(Request $request)
    {
        $user = Auth::user();
        $module = $request->get('module', 'overall');
        $period = $request->get('period', 30); // days
        
        $startDate = Carbon::now()->subDays($period);
        
        $query = Attempt::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at');
        
        if ($module !== 'overall') {
            $query->where('module_type', $module);
        }
        
        $attempts = $query->get();
        
        // Group by date and calculate average scores
        $chartData = $attempts->groupBy(function ($attempt) {
            return $attempt->created_at->format('Y-m-d');
        })->map(function ($dayAttempts) {
            return [
                'date' => $dayAttempts->first()->created_at->format('Y-m-d'),
                'score' => $dayAttempts->avg('score'),
                'attempts' => $dayAttempts->count()
            ];
        })->values();
        
        return response()->json($chartData);
    }

    /**
     * Get overall statistics for user
     */
    private function getOverallStats($user)
    {
        $totalAttempts = Attempt::where('user_id', $user->id)->count();
        $averageScore = Attempt::where('user_id', $user->id)->avg('score') ?? 0;
        
        // Calculate hours spent (rough estimate based on attempts)
        $hoursSpent = $this->calculateHoursSpent($user->id);
        
        // Calculate streak
        $streak = $this->calculateStreak($user->id);
        
        return [
            'totalAttempts' => $totalAttempts,
            'averageScore' => round($averageScore, 1),
            'hoursSpent' => $hoursSpent,
            'streak' => $streak
        ];
    }

    /**
     * Get module-specific statistics
     */
    private function getModuleStats($user)
    {
        $modules = ['reading', 'writing', 'listening', 'speaking'];
        $stats = [];
        
        foreach ($modules as $module) {
            $attempts = Attempt::where('user_id', $user->id)
                ->where('module_type', $module)
                ->get();
            
            $stats[$module] = [
                'attempts' => $attempts->count(),
                'averageScore' => $attempts->count() > 0 ? round($attempts->avg('score'), 1) : 0,
                'bestScore' => $attempts->count() > 0 ? round($attempts->max('score'), 1) : 0,
                'lastAttempt' => $attempts->count() > 0 ? $attempts->max('created_at') : null
            ];
        }
        
        return $stats;
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($user)
    {
        return Attempt::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'module' => $attempt->module_type,
                    'title' => $this->getActivityTitle($attempt),
                    'description' => $this->getActivityDescription($attempt),
                    'score' => round($attempt->score, 1),
                    'date' => $attempt->created_at
                ];
            });
    }

    /**
     * Build leaderboard query
     */
    private function buildLeaderboardQuery($module, $period)
    {
        $query = DB::table('users')
            ->join('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('users.role', 'student')
            ->select(
                'users.id',
                'users.name',
                DB::raw('AVG(attempts.score) as score'),
                DB::raw('COUNT(attempts.id) as attempts'),
                DB::raw('COUNT(DISTINCT attempts.module_type) as modules_completed')
            )
            ->groupBy('users.id', 'users.name');
        
        // Filter by module
        if ($module !== 'overall') {
            $query->where('attempts.module_type', $module);
        }
        
        // Filter by period
        if ($period !== 'all') {
            $startDate = $this->getPeriodStartDate($period);
            $query->where('attempts.created_at', '>=', $startDate);
        }
        
        return $query->orderBy('score', 'desc');
    }

    /**
     * Get user's rank in leaderboard
     */
    private function getUserRank($userId, $module, $period)
    {
        $query = $this->buildLeaderboardQuery($module, $period);
        $allUsers = $query->get();
        
        $userIndex = $allUsers->search(function ($user) use ($userId) {
            return $user->id == $userId;
        });
        
        if ($userIndex === false) {
            return null;
        }
        
        $user = $allUsers[$userIndex];
        
        // Calculate improvement
        $improvement = $this->calculateImprovement($userId, $module, $period);
        
        return [
            'position' => $userIndex + 1,
            'total' => $allUsers->count(),
            'score' => round($user->score, 1),
            'improvement' => $improvement
        ];
    }

    /**
     * Calculate hours spent (rough estimate)
     */
    private function calculateHoursSpent($userId)
    {
        $attempts = Attempt::where('user_id', $userId)->count();
        
        // Rough estimate: 30 minutes per attempt on average
        return round($attempts * 0.5, 1);
    }

    /**
     * Calculate current streak
     */
    private function calculateStreak($userId)
    {
        $attempts = Attempt::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($attempt) {
                return $attempt->created_at->format('Y-m-d');
            });
        
        $streak = 0;
        $currentDate = Carbon::now();
        
        foreach ($attempts as $date => $dayAttempts) {
            $attemptDate = Carbon::parse($date);
            
            if ($currentDate->diffInDays($attemptDate) === $streak) {
                $streak++;
                $currentDate = $attemptDate;
            } else {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate($period)
    {
        switch ($period) {
            case 'week':
                return Carbon::now()->startOfWeek();
            case 'month':
                return Carbon::now()->startOfMonth();
            default:
                return Carbon::now()->subYear();
        }
    }

    /**
     * Calculate improvement percentage
     */
    private function calculateImprovement($userId, $module, $period)
    {
        $currentPeriodStart = $this->getPeriodStartDate($period);
        $previousPeriodStart = $this->getPreviousPeriodStart($period);
        
        $currentQuery = Attempt::where('user_id', $userId)
            ->where('created_at', '>=', $currentPeriodStart);
        
        $previousQuery = Attempt::where('user_id', $userId)
            ->where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<', $currentPeriodStart);
        
        if ($module !== 'overall') {
            $currentQuery->where('module_type', $module);
            $previousQuery->where('module_type', $module);
        }
        
        $currentAvg = $currentQuery->avg('score') ?? 0;
        $previousAvg = $previousQuery->avg('score') ?? 0;
        
        if ($previousAvg == 0) {
            return 0;
        }
        
        return round((($currentAvg - $previousAvg) / $previousAvg) * 100, 1);
    }

    /**
     * Get previous period start date
     */
    private function getPreviousPeriodStart($period)
    {
        switch ($period) {
            case 'week':
                return Carbon::now()->subWeek()->startOfWeek();
            case 'month':
                return Carbon::now()->subMonth()->startOfMonth();
            default:
                return Carbon::now()->subYears(2);
        }
    }

    /**
     * Get activity title
     */
    private function getActivityTitle($attempt)
    {
        $moduleNames = [
            'reading' => 'Reading Practice',
            'writing' => 'Writing Task',
            'listening' => 'Listening Exercise',
            'speaking' => 'Speaking Practice'
        ];
        
        return $moduleNames[$attempt->module_type] ?? 'Practice';
    }

    /**
     * Get activity description
     */
    private function getActivityDescription($attempt)
    {
        return "Completed {$attempt->module_type} module";
    }
}