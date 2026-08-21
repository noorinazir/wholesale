<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'value', 'group', 'description', 'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public static function get(string $key, $default = null)
    {
        $cacheKey = static::cacheKey($key);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $setting = static::where('key', $key)->first();
        if (!$setting) {
            Cache::put($cacheKey, $default, now()->addHour());
            return $default;
        }

        if ($setting->is_encrypted) {
            try {
                $value = Crypt::decrypt($setting->value);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                \Illuminate\Support\Facades\Log::warning('SystemSetting decryption failed for key "' . $key . '": ' . $e->getMessage());
                Cache::put($cacheKey, $default, now()->addHour());
                return $default;
            }
        } else {
            $value = $setting->value;
        }

        Cache::put($cacheKey, $value, now()->addHour());

        return $value;
    }

    public static function set(string $key, $value, string $group = 'general', bool $encrypt = false): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $encrypt ? Crypt::encrypt($value) : $value;
        $setting->group = $group;
        $setting->is_encrypted = $encrypt;
        $setting->save();

        Cache::forget(static::cacheKey($key));
        Cache::put(static::cacheKey($key), $value, now()->addHour());
    }

    public static function flushCache(): void
    {
        if (!Cache::has(static::cacheVersionKey())) {
            Cache::forever(static::cacheVersionKey(), 1);
        }

        Cache::increment(static::cacheVersionKey());
    }

    private static function cacheVersionKey(): string
    {
        return 'system_settings:cache_version';
    }

    private static function cacheKey(string $key): string
    {
        $version = (int) Cache::get(static::cacheVersionKey(), 1);

        return 'system_settings:v' . $version . ':' . $key;
    }
}
