<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_number', 32)->nullable()->unique()->after('username');
            $table->string('account_iban', 34)->nullable()->unique()->after('account_number');
            $table->timestamp('account_issued_at')->nullable()->after('account_iban');
        });

        // Backfill existing accounts
        foreach (DB::table('users')->whereNull('account_number')->orderBy('id')->get() as $user) {
            $number = \App\Support\BankAccount::generateNumber();
            while (DB::table('users')->where('account_number', $number)->exists()) {
                $number = \App\Support\BankAccount::generateNumber();
            }
            DB::table('users')->where('id', $user->id)->update([
                'account_number' => $number,
                'account_iban' => \App\Support\BankAccount::toIban($number),
                'account_issued_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['account_number']);
            $table->dropUnique(['account_iban']);
            $table->dropColumn(['account_number', 'account_iban', 'account_issued_at']);
        });
    }
};
