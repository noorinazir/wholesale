<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    use HasFactory;

    protected static array $cache = [];

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
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }

        $setting = static::where('key', $key)->first();
        if (!$setting) {
            static::$cache[$key] = $default;
            return $default;
        }

        $value = $setting->is_encrypted ? Crypt::decrypt($setting->value) : $setting->value;
        static::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, $value, string $group = 'general', bool $encrypt = false): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $encrypt ? Crypt::encrypt($value) : $value;
        $setting->group = $group;
        $setting->is_encrypted = $encrypt;
        $setting->save();

        static::$cache[$key] = $value;
    }

    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
