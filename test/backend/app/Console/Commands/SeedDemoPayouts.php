<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDemoPayouts extends Command
{
    protected $signature = 'payouts:seed-demo {--recent=3 : Recent payouts to add} {--old=1 : Backdated payouts to add}';

    protected $description = 'Append synthetic completed withdrawals so the public payout feed stays active';

    public function handle(): int
    {
        $users = User::where('is_admin', false)->where('is_frozen', false)->inRandomOrder()->limit(20)->get();

        if ($users->isEmpty()) {
            $this->warn('No users to draw from.');

            return self::SUCCESS;
        }

        $make = function (int $minutesAgo) use ($users) {
            $user = $users->random();
            $at = now()->subMinutes($minutesAgo);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'fund_type' => 'real',
                'amount' => random_int(70, 2200),
                'currency' => 'USD',
                'status' => 'completed',
                'description' => 'Withdrawal',
                'reference' => 'WD-' . strtoupper(Str::random(10)),
                'metadata' => ['method' => 'crypto', 'seed' => true],
                'processed_at' => $at,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        };

        $recent = (int) $this->option('recent');
        $old = (int) $this->option('old');

        for ($i = 0; $i < $recent; $i++) {
            $make(random_int(1, 120));
        }

        for ($i = 0; $i < $old; $i++) {
            $make(random_int(2 * 1440, 9 * 1440));
        }

        $this->info("Added {$recent} recent and {$old} backdated payouts.");

        return self::SUCCESS;
    }
}
