<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attempt;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProgressTrackingService
{
    /**
     * Get comprehensive progress data for a user
     */
    public function getUserProgress($userId)
    {
        $user = User::findOrFail($userId);
        
        return [
            'overall_stats' => $this->getOverallStats($userId),
            'module_breakdown' => $this->getModuleBreakdown($userId),
            'recent_activity' => $this->getRecentActivity($userId),
            'performance_trends' => $this->getPerformanceTrends($userId),
            'achievements' => $this->getUserAchievements($userId),
        ];
    }

    /**
     * Get overall statistics for a user
     */
    public function getOverallStats($userId)
    {
        $totalAttempts = Attempt::byUser($userId)->completed()->count();
        $totalSubmissions = Submission::byUser($userId)->count();
        $totalTimeSpent = Attempt::byUser($userId)->completed()->sum('time_spent');
        
        $averageScore = Attempt::byUser($userId)->completed()->avg('score');
        $bestScore = Attempt::byUser($userId)->completed()->max('score');
        
        $streakDays = $this->calculateStreakDays($userId);
        
        return [
            'total_attempts' => $totalAttempts,
            'total_submissions' => $totalSubmissions,
            'total_time_spent' => $totalTimeSpent, // in seconds
            'average_score' => round($averageScore ?? 0, 2),
            'best_score' => round($bestScore ?? 0, 2),
            'streak_days' => $streakDays,
            'modules_practiced' => $this->getModulesPracticed($userId),
        ];
    }

    /**
     * Get breakdown by module
     */
    public function getModuleBreakdown($userId)
    {
        $modules = ['reading', 'writing', 'listening', 'speaking'];
        $breakdown = [];
        
        foreach ($modules as $module) {
            $attempts = Attempt::byUser($userId)->byModule($module)->completed();
            $submissions = Submission::byUser($userId)->whereHas('task', function($query) use ($module) {
                if ($module === 'writing') {
                    $query->where('submission_type', 'writing');
                } elseif ($module === 'speaking') {
                    $query->where('submission_type', 'speaking');
                }
            });
            
            $breakdown[$module] = [
                'attempts_count' => $attempts->count(),
                'submissions_count' => $module === 'writing' || $module === 'speaking' ? $submissions->count() : 0,
                'average_score' => round($attempts->avg('score') ?? 0, 2),
                'best_score' => round($attempts->max('score') ?? 0, 2),
                'total_time_spent' => $attempts->sum('time_spent'),
                'improvement_rate' => $this->calculateImprovementRate($userId, $module),
            ];
        }
        
        return $breakdown;
    }

    /**
     * Get recent activity for a user
     */
    public function getRecentActivity($userId, $limit = 10)
    {
        $recentAttempts = Attempt::with(['content'])
            ->byUser($userId)
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
            
        $recentSubmissions = Submission::with(['task'])
            ->byUser($userId)
            ->orderBy('submitted_at', 'desc')
            ->limit($limit)
            ->get();
            
        // Combine and sort by date
        $activities = collect();
        
        foreach ($recentAttempts as $attempt) {
            $activities->push([
                'type' => 'attempt',
                'module' => $attempt->module_type,
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'percentage' => $attempt->percentage,
                'date' => $attempt->completed_at,
                'content_title' => $attempt->content->title ?? 'Unknown',
            ]);
        }
        
        foreach ($recentSubmissions as $submission) {
            $activities->push([
                'type' => 'submission',
                'module' => $submission->submission_type,
                'score' => $submission->score,
                'date' => $submission->submitted_at,
                'content_title' => $submission->task->title ?? 'Unknown',
            ]);
        }
        
        return $activities->sortByDesc('date')->take($limit)->values();
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends($userId, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        
        $dailyStats = Attempt::byUser($userId)
            ->completed()
            ->where('completed_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('COUNT(*) as attempts_count'),
                DB::raw('AVG(score) as average_score'),
                DB::raw('SUM(time_spent) as total_time')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return $dailyStats->map(function ($stat) {
            return [
                'date' => $stat->date,
                'attempts_count' => $stat->attempts_count,
                'average_score' => round($stat->average_score, 2),
                'total_time' => $stat->total_time,
            ];
        });
    }

    /**
     * Calculate user achievements
     */
    public function getUserAchievements($userId)
    {
        $achievements = [];
        
        // First attempt achievement
        $firstAttempt = Attempt::byUser($userId)->completed()->oldest('completed_at')->first();
        if ($firstAttempt) {
            $achievements[] = [
                'title' => 'First Steps',
                'description' => 'Completed your first practice session',
                'earned_at' => $firstAttempt->completed_at,
                'icon' => '🎯'
            ];
        }
        
        // Module completion achievements
        $modules = ['reading', 'writing', 'listening', 'speaking'];
        foreach ($modules as $module) {
            $moduleAttempts = Attempt::byUser($userId)->byModule($module)->completed()->count();
            if ($moduleAttempts >= 5) {
                $achievements[] = [
                    'title' => ucfirst($module) . ' Enthusiast',
                    'description' => "Completed 5+ {$module} practice sessions",
                    'earned_at' => Attempt::byUser($userId)->byModule($module)->completed()->latest('completed_at')->first()->completed_at,
                    'icon' => $this->getModuleIcon($module)
                ];
            }
        }
        
        // High score achievements
        $bestScore = Attempt::byUser($userId)->completed()->max('score');
        if ($bestScore >= 90) {
            $achievements[] = [
                'title' => 'Excellence',
                'description' => 'Achieved a score of 90% or higher',
                'earned_at' => Attempt::byUser($userId)->completed()->where('score', $bestScore)->first()->completed_at,
                'icon' => '🏆'
            ];
        }
        
        // Streak achievements
        $streakDays = $this->calculateStreakDays($userId);
        if ($streakDays >= 7) {
            $achievements[] = [
                'title' => 'Consistent Learner',
                'description' => 'Practiced for 7 consecutive days',
                'earned_at' => Carbon::now(),
                'icon' => '🔥'
            ];
        }
        
        return collect($achievements)->sortByDesc('earned_at')->values();
    }

    /**
     * Calculate streak days for a user
     */
    private function calculateStreakDays($userId)
    {
        $attempts = Attempt::byUser($userId)
            ->completed()
            ->select(DB::raw('DATE(completed_at) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->toArray();
            
        if (empty($attempts)) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = Carbon::now()->format('Y-m-d');
        
        foreach ($attempts as $date) {
            if ($date === $currentDate || $date === Carbon::parse($currentDate)->subDays($streak)->format('Y-m-d')) {
                $streak++;
                $currentDate = Carbon::parse($date)->subDay()->format('Y-m-d');
            } else {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Get modules practiced by user
     */
    private function getModulesPracticed($userId)
    {
        return Attempt::byUser($userId)
            ->completed()
            ->distinct()
            ->pluck('module_type')
            ->count();
    }

    /**
     * Calculate improvement rate for a module
     */
    private function calculateImprovementRate($userId, $module)
    {
        $attempts = Attempt::byUser($userId)
            ->byModule($module)
            ->completed()
            ->orderBy('completed_at')
            ->pluck('score');
            
        if ($attempts->count() < 2) {
            return 0;
        }
        
        $firstScore = $attempts->first();
        $lastScore = $attempts->last();
        
        if ($firstScore == 0) {
            return $lastScore > 0 ? 100 : 0;
        }
        
        return round((($lastScore - $firstScore) / $firstScore) * 100, 2);
    }

    /**
     * Get module icon
     */
    private function getModuleIcon($module)
    {
        $icons = [
            'reading' => '📖',
            'writing' => '✍️',
            'listening' => '🎧',
            'speaking' => '🎤'
        ];
        
        return $icons[$module] ?? '📚';
    }
}