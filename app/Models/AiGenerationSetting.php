<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiGenerationSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Get a setting value with caching
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("ai_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type
            ]
        );

        Cache::forget("ai_setting_{$key}");
        
        return $setting;
    }

    /**
     * Cast value based on type
     */
    private static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get all settings as array
     */
    public static function getAll()
    {
        return Cache::remember('ai_settings_all', 3600, function () {
            $settings = self::all();
            $result = [];
            
            foreach ($settings as $setting) {
                $result[$setting->key] = self::castValue($setting->value, $setting->type);
            }
            
            return $result;
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        Cache::forget('ai_settings_all');
        
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("ai_setting_{$setting->key}");
        }
    }
}
