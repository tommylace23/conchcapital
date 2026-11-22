<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->nullable()->unique()->after('username');
            $table->foreignId('referred_by')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
            $table->decimal('referral_bonus_amount', 15, 2)->nullable()->after('referred_by')
                ->comment('Bonus credited to a new user who joins with THIS user\'s code');
        });

        DB::table('users')->whereNull('referral_code')->orderBy('id')->each(function ($user) {
            do {
                $code = strtoupper(Str::random(8));
            } while (DB::table('users')->where('referral_code', $code)->exists());

            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by', 'referral_bonus_amount']);
        });
    }
};
