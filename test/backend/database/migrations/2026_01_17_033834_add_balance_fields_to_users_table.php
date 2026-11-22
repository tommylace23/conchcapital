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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('real_balance', 15, 2)->default(0)->after('avatar_url');
            $table->decimal('simulated_balance', 15, 2)->default(0)->after('real_balance');
            $table->string('balance_currency', 3)->default('USD')->after('simulated_balance');
            $table->boolean('is_admin')->default(false)->after('balance_currency');
            $table->boolean('is_frozen')->default(false)->after('is_admin');
            $table->timestamp('frozen_at')->nullable()->after('is_frozen');
            $table->text('freeze_reason')->nullable()->after('frozen_at');
            $table->timestamp('last_active_at')->nullable()->after('freeze_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'real_balance',
                'simulated_balance',
                'balance_currency',
                'is_admin',
                'is_frozen',
                'frozen_at',
                'freeze_reason',
                'last_active_at'
            ]);
        });
    }
};
