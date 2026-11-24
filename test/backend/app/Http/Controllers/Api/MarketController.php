<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class MarketController extends Controller
{
    private const STOCKS = [
        'AAPL' => 'Apple',
        'MSFT' => 'Microsoft',
        'NVDA' => 'NVIDIA',
        'AMZN' => 'Amazon',
        'GOOGL' => 'Alphabet',
        'TSLA' => 'Tesla',
        'META' => 'Meta',
        'JPM' => 'JPMorgan',
    ];

    private const CRYPTO = [
        'bitcoin' => 'BTC',
        'ethereum' => 'ETH',
        'solana' => 'SOL',
        'ripple' => 'XRP',
        'cardano' => 'ADA',
        'dogecoin' => 'DOGE',
    ];

    /**
     * Cached market snapshot: US equities + major crypto.
     */
    public function overview()
    {
        $data = Cache::remember('market.overview', now()->addMinutes(5), function () {
            return $this->build();
        });

        return response()->json($data);
    }

    private function build(): array
    {
        $stocks = $this->fetchStocks();
        $crypto = $this->fetchCrypto();

        $payload = [
            'stocks' => $stocks,
            'crypto' => $crypto,
            'updated_at' => now()->toIso8601String(),
        ];

        // Keep the last good snapshot for when an upstream API is unavailable
        if ($stocks || $crypto) {
            Cache::forever('market.last', $payload);
        }

        if (! $stocks && ! $crypto) {
            return Cache::get('market.last', ['stocks' => [], 'crypto' => [], 'updated_at' => now()->toIso8601String()]);
        }

        return $payload;
    }

    /**
     * Pull quotes from Yahoo Finance's public chart endpoint.
     */
    private function fetchStocks(): array
    {
        $symbols = array_keys(self::STOCKS);
        $urls = [];
        foreach ($symbols as $symbol) {
            $urls[$symbol] = 'https://query1.finance.yahoo.com/v8/finance/chart/' . $symbol . '?interval=1d&range=2d';
        }

        $responses = $this->multiGet($urls);
        $out = [];

        foreach ($responses as $symbol => $body) {
            $json = json_decode($body, true);
            $meta = $json['chart']['result'][0]['meta'] ?? null;

            if (! $meta || ! isset($meta['regularMarketPrice'])) {
                continue;
            }

            $price = (float) $meta['regularMarketPrice'];
            $prev = (float) ($meta['chartPreviousClose'] ?? $meta['previousClose'] ?? 0);
            $change = $prev > 0 ? (($price - $prev) / $prev) * 100 : 0;

            $out[] = [
                'symbol' => $symbol,
                'name' => self::STOCKS[$symbol],
                'price' => round($price, 2),
                'change_pct' => round($change, 2),
            ];
        }

        return $out;
    }

    /**
     * Pull crypto quotes from CoinGecko (no key required).
     */
    private function fetchCrypto(): array
    {
        $ids = implode(',', array_keys(self::CRYPTO));
        $url = "https://api.coingecko.com/api/v3/simple/price?ids={$ids}&vs_currencies=usd&include_24hr_change=true";

        $body = $this->multiGet(['crypto' => $url])['crypto'] ?? '';
        $json = json_decode($body, true);

        if (! is_array($json)) {
            return [];
        }

        $out = [];
        foreach (self::CRYPTO as $id => $symbol) {
            if (! isset($json[$id]['usd'])) {
                continue;
            }

            $out[] = [
                'symbol' => $symbol,
                'name' => ucfirst($id),
                'price' => (float) $json[$id]['usd'],
                'change_pct' => round((float) ($json[$id]['usd_24h_change'] ?? 0), 2),
            ];
        }

        return $out;
    }

    /**
     * Small parallel HTTP GET helper.
     */
    private function multiGet(array $urls, int $timeout = 6): array
    {
        $multi = curl_multi_init();
        $handles = [];

        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ConchCapital/1.0)',
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi, 1.0);
        } while ($running > 0);

        $out = [];
        foreach ($handles as $key => $ch) {
            $out[$key] = (string) curl_multi_getcontent($ch);
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);

        return $out;
    }
}
