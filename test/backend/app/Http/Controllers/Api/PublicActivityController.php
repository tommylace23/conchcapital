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
        $limit = 12;

        $payouts = Transaction::query()
            ->with('user:id,display_name,username,name')
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->latest('processed_at')
            ->latest('id')
            ->limit($limit)
            ->get()
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
