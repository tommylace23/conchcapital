<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profit_growths', function (Blueprint $table) {
            // Lock period - funds are locked until this date
            $table->timestamp('lock_until_date')->nullable()->after('next_growth_calculation_at')->comment('Funds locked until this date (7 days from transfer)');
            
            // Track last transfer date for cooldown (users can only transfer once per week)
            $table->timestamp('last_transfer_date')->nullable()->after('lock_until_date')->comment('Last time funds were transferred to this profit growth');
            
            // Track the locked amount (amount that's currently locked)
            $table->decimal('locked_amount', 15, 2)->default(0)->after('last_transfer_date')->comment('Amount currently locked (cannot be withdrawn)');
            
            // Index for querying locked profit growths
            $table->index('lock_until_date');
            $table->index('last_transfer_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profit_growths', function (Blueprint $table) {
            $table->dropIndex(['lock_until_date']);
            $table->dropIndex(['last_transfer_date']);
            $table->dropColumn([
                'lock_until_date',
                'last_transfer_date',
                'locked_amount',
            ]);
        });
    }
};
