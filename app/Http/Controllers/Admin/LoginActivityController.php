<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginActivityController extends Controller
{
    /**
     * Get all login activities with filters
     */
    public function index(Request $request)
    {
        $query = LoginActivity::with('user:id,name,email,role')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by device type
        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by email or mobile
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 20);
        $activities = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Get login activity statistics
     */
    public function getStats(Request $request)
    {
        $days = $request->input('days', 30);
        $fromDate = now()->subDays($days);

        $stats = [
            'total_logins' => LoginActivity::where('status', 'success')
                ->where('created_at', '>=', $fromDate)
                ->count(),
            
            'failed_attempts' => LoginActivity::where('status', 'failed')
                ->where('created_at', '>=', $fromDate)
                ->count(),
            
            'unique_users' => LoginActivity::where('status', 'success')
                ->where('created_at', '>=', $fromDate)
                ->distinct('user_id')
                ->count('user_id'),
            
            'mobile_logins' => LoginActivity::where('status', 'success')
                ->where('device_type', 'mobile')
                ->where('created_at', '>=', $fromDate)
                ->count(),
            
            'web_logins' => LoginActivity::where('status', 'success')
                ->where('device_type', 'web')
                ->where('created_at', '>=', $fromDate)
                ->count(),
            
            'active_sessions' => LoginActivity::where('status', 'success')
                ->whereNull('logged_out_at')
                ->count(),
        ];

        // Login trends by day
        $loginTrends = LoginActivity::where('status', 'success')
            ->where('created_at', '>=', $fromDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Failed login trends
        $failedTrends = LoginActivity::where('status', 'failed')
            ->where('created_at', '>=', $fromDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top users by login count
        $topUsers = LoginActivity::where('status', 'success')
            ->where('created_at', '>=', $fromDate)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as login_count'))
            ->groupBy('user_id')
            ->orderBy('login_count', 'desc')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        // Recent failed attempts
        $recentFailures = LoginActivity::where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'login_trends' => $loginTrends,
            'failed_trends' => $failedTrends,
            'top_users' => $topUsers,
            'recent_failures' => $recentFailures
        ]);
    }

    /**
     * Get login activities for a specific user
     */
    public function getUserActivities(Request $request, $userId)
    {
        $limit = $request->input('limit', 20);
        
        $activities = LoginActivity::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $user = User::find($userId);

        return response()->json([
            'success' => true,
            'user' => $user,
            'activities' => $activities
        ]);
    }

    /**
     * Get failed login attempts
     */
    public function getFailedAttempts(Request $request)
    {
        $hours = $request->input('hours', 24);
        $fromDate = now()->subHours($hours);

        $failedAttempts = LoginActivity::where('status', 'failed')
            ->where('created_at', '>=', $fromDate)
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by email/mobile to find suspicious activity
        $suspiciousActivity = LoginActivity::where('status', 'failed')
            ->where('created_at', '>=', $fromDate)
            ->select(
                DB::raw('COALESCE(email, mobile) as identifier'),
                DB::raw('COUNT(*) as attempt_count'),
                DB::raw('MAX(created_at) as last_attempt')
            )
            ->groupBy('identifier')
            ->having('attempt_count', '>=', 3)
            ->orderBy('attempt_count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'failed_attempts' => $failedAttempts,
            'suspicious_activity' => $suspiciousActivity
        ]);
    }

    /**
     * Delete a login activity record
     */
    public function destroy($id)
    {
        $activity = LoginActivity::findOrFail($id);
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login activity deleted successfully'
        ]);
    }
}
