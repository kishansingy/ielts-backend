<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeminiUsageTracking extends Model
{
    use HasFactory;

    protected $table = 'gemini_usage_tracking';

    protected $fillable = [
        'model_used',
        'module_type',
        'band_level',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'request_type',
        'success',
        'error_message',
        'requested_at',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'success' => 'boolean',
        'requested_at' => 'datetime',
    ];

    /**
     * Get daily usage statistics
     */
    public static function getDailyStats($date = null)
    {
        $date = $date ?? now()->toDateString();
        
        return self::whereDate('requested_at', $date)
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost,
                module_type,
                model_used
            ')
            ->groupBy('module_type', 'model_used')
            ->get();
    }

    /**
     * Get monthly usage statistics
     */
    public static function getMonthlyStats($year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return self::whereYear('requested_at', $year)
            ->whereMonth('requested_at', $month)
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost,
                DATE(requested_at) as date
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Check if approaching daily limit
     */
    public static function isApproachingDailyLimit($limit = 1500)
    {
        $today = now()->toDateString();
        $count = self::whereDate('requested_at', $today)->count();
        
        return [
            'count' => $count,
            'limit' => $limit,
            'remaining' => $limit - $count,
            'percentage' => ($count / $limit) * 100,
            'approaching_limit' => $count > ($limit * 0.8) // 80% threshold
        ];
    }
}
