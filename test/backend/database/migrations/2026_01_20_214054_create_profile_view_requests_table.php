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
        Schema::create('profile_view_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('profile_owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->timestamp('viewed_at')->nullable(); // When owner responds
            $table->timestamps();
            
            // Prevent duplicate pending requests (only for pending status)
            // Note: We'll handle this in application logic since we want to allow multiple declined requests
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_view_requests');
    }
};
