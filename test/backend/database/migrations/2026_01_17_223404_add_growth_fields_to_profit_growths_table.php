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
            $table->decimal('growth_percentage', 5, 2)->default(0)->after('current_balance')->comment('Growth percentage (1-100)');
            $table->enum('growth_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('daily')->after('growth_percentage');
            $table->timestamp('last_growth_calculated_at')->nullable()->after('growth_frequency');
            $table->timestamp('next_growth_calculation_at')->nullable()->after('last_growth_calculated_at')->index();
            $table->boolean('auto_calculate')->default(true)->after('next_growth_calculation_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profit_growths', function (Blueprint $table) {
            $table->dropColumn([
                'growth_percentage',
                'growth_frequency',
                'last_growth_calculated_at',
                'next_growth_calculation_at',
                'auto_calculate',
            ]);
        });
    }
};
