<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Get a setting by key, auto-decrypting if encrypted.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting || $setting->value === null) {
            return $default;
        }

        if ($setting->is_encrypted) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                return $default;
            }
        }

        return $setting->value;
    }

    /**
     * Set a setting value, encrypting if specified.
     */
    public static function set(string $key, mixed $value, string $group = 'general', bool $isEncrypted = false): static
    {
        $storedValue = $value;
        if ($isEncrypted && !empty($value)) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $storedValue,
                'is_encrypted' => $isEncrypted,
            ]
        );
    }

    /**
     * Get all settings in a group as key-value associative array.
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            if ($setting->value === null) {
                $result[$setting->key] = null;
                continue;
            }

            if ($setting->is_encrypted) {
                try {
                    $result[$setting->key] = Crypt::decryptString($setting->value);
                } catch (\Exception $e) {
                    $result[$setting->key] = null;
                }
            } else {
                $result[$setting->key] = $setting->value;
            }
        }

        return $result;
    }
}
