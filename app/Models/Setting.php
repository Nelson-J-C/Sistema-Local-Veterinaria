<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['group', 'key', 'value', 'type'])]
class Setting extends Model
{
    /**
     * Clear the cached value whenever a setting changes.
     */
    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => $setting->forgetCache());
        static::deleted(fn (Setting $setting) => $setting->forgetCache());
    }

    /**
     * Retrieve a setting value, optionally scoped to a group.
     */
    public static function get(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $setting = Cache::rememberForever(
            self::cacheKey($group, $key),
            fn (): ?self => self::query()
                ->where('group', $group)
                ->where('key', $key)
                ->first(),
        );

        return $setting?->typedValue() ?? $default;
    }

    /**
     * Create or update a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        return self::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => self::serialize($value, $type), 'type' => $type],
        );
    }

    /**
     * Convert the stored value to its declared type.
     */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float', 'decimal' => (float) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }

    /**
     * Serialize a value for storage.
     */
    protected static function serialize(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }

    /**
     * Build the cache key for a setting.
     */
    protected static function cacheKey(string $group, string $key): string
    {
        return "settings:{$group}:{$key}";
    }

    /**
     * Forget the cached value for this setting.
     */
    protected function forgetCache(): void
    {
        Cache::forget(self::cacheKey($this->group, $this->key));
    }
}
