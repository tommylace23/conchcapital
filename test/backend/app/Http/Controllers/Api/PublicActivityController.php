<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;

class PublicActivityController extends Controller
{
    /**
     * Public, privacy-safe feed of recent completed payouts.
     * Names are shortened to "First L." and no ids/emails are exposed.
     */
    public function recentPayouts()
    {
        $base = fn () => Transaction::query()
            ->with('user:id,display_name,username,name')
            ->where('type', 'withdrawal')
            ->where('status', 'completed');

        // A blend reads more naturally than twelve identical "just now" entries:
        // most from the last day, a few from the previous week.
        $recent = $base()->where('processed_at', '>=', now()->subDay())
            ->latest('processed_at')->limit(8)->get();

        // Older entries split across two buckets so ages vary (2-4 days / 4-10 days)
        $midAgo = $base()->whereBetween('processed_at', [now()->subDays(10), now()->subDays(4)])
            ->latest('processed_at')->limit(2)->get();

        $midRecent = $base()->whereBetween('processed_at', [now()->subDays(4), now()->subDay()])
            ->latest('processed_at')->limit(2)->get();

        $payouts = $recent->concat($midRecent)->concat($midAgo)
            ->shuffle()
            ->map(function (Transaction $t) {
                $name = $t->user?->display_name
                    ?: $t->user?->name
                    ?: $t->user?->username
                    ?: 'Investor';

                return [
                    'name' => $this->maskName($name),
                    'amount' => (float) $t->amount,
                    'currency' => $t->currency ?? 'USD',
                    'at' => ($t->processed_at ?? $t->updated_at ?? $t->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'payouts' => $payouts,
            'count' => $payouts->count(),
        ]);
    }

    private function maskName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Investor';
        }

        $name = ltrim($name, '@');
        $parts = preg_split('/\s+/', $name) ?: [$name];
        $first = $parts[0];

        if (count($parts) > 1) {
            return $first . ' ' . strtoupper(substr($parts[count($parts) - 1], 0, 1)) . '.';
        }

        return $first;
    }
}
