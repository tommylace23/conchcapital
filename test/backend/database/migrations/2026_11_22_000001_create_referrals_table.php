<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code', 16);
            $table->decimal('bonus_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'credited'])->default('credited')->index();
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
