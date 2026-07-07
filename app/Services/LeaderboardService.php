<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attempt;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardService
{
    /**
     * Get overall leaderboard
     */
    public function getOverallLeaderboard($limit = 10)
    {
        $users = User::students()
            ->select('users.*')
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('users.id')
            ->selectRaw('
                users.*,
                COUNT(attempts.id) as total_attempts,
                AVG(attempts.score) as average_score,
                MAX(attempts.score) as best_score,
                SUM(attempts.time_spent) as total_time_spent
            ')
            ->orderByDesc('average_score')
            ->orderByDesc('total_attempts')
            ->limit($limit)
            ->get();

        return $users->map(function ($user, $index) {
            return [
                'rank' => $index + 1,
                'user_id' => $user->id,
                'name' => $user->name,
                'total_attempts' => $user->total_attempts ?? 0,
                'average_score' => round($user->average_score ?? 0, 2),
                'best_score' => round($user->best_score ?? 0, 2),
                'total_time_spent' => $user->total_time_spent ?? 0,
            ];
        });
    }

    /**
     * Get module-specific leaderboard
     */
    public function getModuleLeaderboard($module, $limit = 10)
    {
        $users = User::students()
            ->select('users.*')
            ->leftJoin('attempts', function($join) use ($module) {
                $join->on('users.id', '=', 'attempts.user_id')
                     ->where('attempts.module_type', '=', $module)
                     ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30));
            })
            ->groupBy('users.id')
            ->selectRaw('
                users.*,
                COUNT(attempts.id) as total_attempts,
                AVG(attempts.score) as average_score,
                MAX(attempts.score) as best_score,
                SUM(attempts.time_spent) as total_time_spent
            ')
            ->having('total_attempts', '>', 0)
            ->orderByDesc('average_score')
            ->orderByDesc('total_attempts')
            ->limit($limit)
            ->get();

        return $users->map(function ($user, $index) {
            return [
                'rank' => $index + 1,
                'user_id' => $user->id,
                'name' => $user->name,
                'total_attempts' => $user->total_attempts,
                'average_score' => round($user->average_score, 2),
                'best_score' => round($user->best_score, 2),
                'total_time_spent' => $user->total_time_spent,
            ];
        });
    }

    /**
     * Get weekly leaderboard
     */
    public function getWeeklyLeaderboard($limit = 10)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        $users = User::students()
            ->select('users.*')
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', $startOfWeek)
            ->groupBy('users.id')
            ->selectRaw('
                users.*,
                COUNT(attempts.id) as weekly_attempts,
                AVG(attempts.score) as weekly_average_score,
                MAX(attempts.score) as weekly_best_score,
                SUM(attempts.time_spent) as weekly_time_spent
            ')
            ->having('weekly_attempts', '>', 0)
            ->orderByDesc('weekly_average_score')
            ->orderByDesc('weekly_attempts')
            ->limit($limit)
            ->get();

        return $users->map(function ($user, $index) {
            return [
                'rank' => $index + 1,
                'user_id' => $user->id,
                'name' => $user->name,
                'weekly_attempts' => $user->weekly_attempts,
                'weekly_average_score' => round($user->weekly_average_score, 2),
                'weekly_best_score' => round($user->weekly_best_score, 2),
                'weekly_time_spent' => $user->weekly_time_spent,
            ];
        });
    }

    /**
     * Get user's position in leaderboard
     */
    public function getUserPosition($userId, $type = 'overall', $module = null)
    {
        switch ($type) {
            case 'weekly':
                return $this->getUserWeeklyPosition($userId);
            case 'module':
                return $this->getUserModulePosition($userId, $module);
            default:
                return $this->getUserOverallPosition($userId);
        }
    }

    /**
     * Get user's overall position
     */
    private function getUserOverallPosition($userId)
    {
        $userStats = User::where('id', $userId)
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('users.id')
            ->selectRaw('
                AVG(attempts.score) as average_score,
                COUNT(attempts.id) as total_attempts
            ')
            ->first();

        if (!$userStats || $userStats->total_attempts == 0) {
            return null;
        }

        $betterUsers = User::students()
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30))
            ->where('users.id', '!=', $userId)
            ->groupBy('users.id')
            ->selectRaw('
                AVG(attempts.score) as average_score,
                COUNT(attempts.id) as total_attempts
            ')
            ->having('average_score', '>', $userStats->average_score)
            ->orHaving(function($query) use ($userStats) {
                $query->where('average_score', '=', $userStats->average_score)
                      ->having('total_attempts', '>', $userStats->total_attempts);
            })
            ->count();

        return $betterUsers + 1;
    }

    /**
     * Get user's weekly position
     */
    private function getUserWeeklyPosition($userId)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        $userStats = User::where('id', $userId)
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', $startOfWeek)
            ->groupBy('users.id')
            ->selectRaw('
                AVG(attempts.score) as weekly_average_score,
                COUNT(attempts.id) as weekly_attempts
            ')
            ->first();

        if (!$userStats || $userStats->weekly_attempts == 0) {
            return null;
        }

        $betterUsers = User::students()
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->where('attempts.completed_at', '>=', $startOfWeek)
            ->where('users.id', '!=', $userId)
            ->groupBy('users.id')
            ->selectRaw('
                AVG(attempts.score) as weekly_average_score,
                COUNT(attempts.id) as weekly_attempts
            ')
            ->having('weekly_average_score', '>', $userStats->weekly_average_score)
            ->orHaving(function($query) use ($userStats) {
                $query->where('weekly_average_score', '=', $userStats->weekly_average_score)
                      ->having('weekly_attempts', '>', $userStats->weekly_attempts);
            })
            ->count();

        return $betterUsers + 1;
    }

    /**
     * Get user's module position
     */
    private function getUserModulePosition($userId, $module)
    {
        $userStats = User::where('id', $userId)
            ->leftJoin('attempts', function($join) use ($module) {
                $join->on('users.id', '=', 'attempts.user_id')
                     ->where('attempts.module_type', '=', $module)
                     ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30));
            })
            ->groupBy('users.id')
            ->selectRaw('
                AVG(attempts.score) as average_score,
                COUNT(attempts.id) as total_attempts
            ')
            ->first();

        if (!$userStats || $userStats->total_attempts == 0) {
            return null;
        }

        $betterUsers = User::students()
            ->leftJoin('attempts', function($join) use ($module) {
                $join->on('users.id', '=', 'attempts.user_id')
                     ->where('attempts.module_type', '=', $module)
                     ->where('attempts.completed_at', '>=', Carbon::now()->subDays(30));
            })
            ->where('users.id', '!=', $userId)
            ->groupBy('users.id')
            ->selectRaw('
                AVG(attempts.score) as average_score,
                COUNT(attempts.id) as total_attempts
            ')
            ->having('average_score', '>', $userStats->average_score)
            ->orHaving(function($query) use ($userStats) {
                $query->where('average_score', '=', $userStats->average_score)
                      ->having('total_attempts', '>', $userStats->total_attempts);
            })
            ->count();

        return $betterUsers + 1;
    }
}