<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CryptoAddresses
{
    public const CACHE_KEY = 'platform.crypto_addresses';

    public const KEYS = [
        'btc_address'  => 'crypto.btc_address',
        'eth_address'  => 'crypto.eth_address',
        'usdt_address' => 'crypto.usdt_address',
    ];

    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = DB::table('platform_settings')->whereIn('key', array_keys(self::KEYS))->pluck('value', 'key')->toArray();

            $out = [];
            foreach (self::KEYS as $key => $configPath) {
                $value = $stored[$key] ?? null;
                $out[$key] = ($value === null || $value === '') ? config($configPath) : $value;
            }

            return $out;
        });
    }

    public static function for(string $symbol): ?string
    {
        $map = ['BTC' => 'btc_address', 'ETH' => 'eth_address', 'USDT' => 'usdt_address'];

        return self::all()[$map[strtoupper($symbol)] ?? 'btc_address'] ?? null;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::KEYS)) continue;
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        Cache::forget(self::CACHE_KEY);
    }
}
