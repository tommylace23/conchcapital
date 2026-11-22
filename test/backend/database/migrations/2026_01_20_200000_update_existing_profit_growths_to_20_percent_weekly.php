<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all existing profit growths to 20% weekly
        DB::table('profit_growths')
            ->where('status', '!=', 'cancelled')
            ->update([
                'growth_percentage' => 20.0,
                'growth_frequency' => 'weekly',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as we don't know the original values
        // This is a one-way migration
    }
};
