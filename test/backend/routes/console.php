<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

// Run every minute to catch test profit growths (5 minutes) and other frequencies
// The command itself checks next_growth_calculation_at, so it only processes due ones
Schedule::command('profit-growth:calculate')->everyMinute();

// Keep the public payout feed topped up with a realistic mix of recent and older entries
Schedule::command('payouts:seed-demo')->hourly();
