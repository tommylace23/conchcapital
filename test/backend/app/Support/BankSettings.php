<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BankSettings
{
    public const CACHE_KEY = 'platform.bank_settings';

    /**
     * Settings keys and the config path each falls back to.
     */
    public const KEYS = [
        'bank_name' => 'banking.bank_name',
        'bank_address' => 'banking.bank_address',
        'bank_swift' => 'banking.swift',
        'bank_routing' => 'banking.routing',
        'bank_account_holder' => 'banking.account_holder',
        'bank_support_email' => 'banking.support_email',
        'bank_support_phone' => 'banking.support_phone',
        'deposit_reference_prefix' => 'banking.deposit_reference_prefix',
        'rm_name' => 'banking.rm_name',
        'rm_title' => 'banking.rm_title',
        'rm_email' => 'banking.rm_email',
        'rm_phone' => 'banking.rm_phone',
        'rm_booking_url' => 'banking.rm_booking_url',
    ];

    /**
     * All bank settings resolved: database value, else config default.
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = DB::table('platform_settings')
                ->whereIn('key', array_keys(self::KEYS))
                ->pluck('value', 'key')
                ->toArray();

            $resolved = [];
            foreach (self::KEYS as $key => $configPath) {
                $value = $stored[$key] ?? null;
                $resolved[$key] = ($value === null || $value === '')
                    ? config($configPath)
                    : $value;
            }

            return $resolved;
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Persist a subset of settings. Only known keys are accepted.
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::KEYS)) {
                continue;
            }

            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Remove stored overrides so config defaults apply again.
     */
    public static function reset(array $keys = []): void
    {
        $keys = $keys ?: array_keys(self::KEYS);
        DB::table('platform_settings')->whereIn('key', $keys)->delete();
        Cache::forget(self::CACHE_KEY);
    }
}
