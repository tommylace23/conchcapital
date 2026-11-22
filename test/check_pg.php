<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$pgs = DB::table('profit_growths')->get();

echo "=== PROFIT GROWTH STATUS REPORT ===\n\n";
echo "Total Profit Growths: " . $pgs->count() . "\n\n";

if ($pgs->count() > 0) {
    echo "ID | User | Name | Balance | Growth % | Frequency | Status | Lock Until\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($pgs as $pg) {
        $balance = $pg->current_balance ?? $pg->balance ?? 0;
        $lockInfo = $pg->lock_until_date ? date('Y-m-d', strtotime($pg->lock_until_date)) : 'N/A';
        printf("%-3d | %-4d | %-15s | %-8.2f | %-8.1f%% | %-9s | %-6s | %s\n",
            $pg->id,
            $pg->user_id,
            substr($pg->name ?? 'N/A', 0, 15),
            $balance,
            $pg->growth_percentage,
            $pg->growth_frequency,
            $pg->status,
            $lockInfo
        );
    }
    
    echo "\n=== SUMMARY ===\n";
    
    $byFreq = [];
    $byPct = [];
    $byStatus = [];
    $activePaused = [];
    
    foreach ($pgs as $pg) {
        $byFreq[$pg->growth_frequency] = ($byFreq[$pg->growth_frequency] ?? 0) + 1;
        $byPct[$pg->growth_percentage] = ($byPct[$pg->growth_percentage] ?? 0) + 1;
        $byStatus[$pg->status] = ($byStatus[$pg->status] ?? 0) + 1;
        
        if (in_array($pg->status, ['active', 'paused'])) {
            $activePaused[] = $pg;
        }
    }
    
    echo "\nBy Frequency:\n";
    foreach ($byFreq as $freq => $count) {
        echo "  {$freq}: {$count}\n";
    }
    
    echo "\nBy Growth Percentage:\n";
    foreach ($byPct as $pct => $count) {
        echo "  {$pct}%: {$count}\n";
    }
    
    echo "\nBy Status:\n";
    foreach ($byStatus as $status => $count) {
        echo "  {$status}: {$count}\n";
    }
    
    echo "\n=== VERIFICATION ===\n";
    $not20Weekly = array_filter($activePaused, function($pg) {
        return $pg->growth_percentage != 20.0 || $pg->growth_frequency != 'weekly';
    });
    
    if (count($not20Weekly) == 0) {
        echo "✅ All active/paused profit growths are set to 20% weekly\n";
    } else {
        echo "⚠️  Found " . count($not20Weekly) . " active/paused profit growth(s) NOT set to 20% weekly:\n";
        foreach ($not20Weekly as $pg) {
            echo "   - ID {$pg->id} (User {$pg->user_id}): {$pg->growth_percentage}% {$pg->growth_frequency}\n";
        }
    }
    
    echo "\n=== CALCULATION LOGIC ===\n";
    echo "For 20% weekly growth during 7-day lock period:\n";
    echo "  - Daily growth: " . number_format(20.0 / 7, 3) . "% (≈2.857%)\n";
    echo "  - Calculated incrementally each day during lock period\n";
    echo "  - After lock ends, switches to weekly frequency\n";
}

echo "\n=== END ===\n";
