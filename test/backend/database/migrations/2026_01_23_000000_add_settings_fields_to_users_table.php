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
            // Account settings
            $table->string('language', 10)->nullable()->after('last_active_at');
            $table->string('timezone', 50)->nullable()->after('language');
            
            // Notification preferences
            $table->boolean('email_notifications_enabled')->default(true)->after('timezone');
            $table->boolean('push_notifications_enabled')->default(true)->after('email_notifications_enabled');
            $table->boolean('marketing_emails_enabled')->default(false)->after('push_notifications_enabled');
            
            // Two-Factor Authentication
            $table->boolean('two_factor_enabled')->default(false)->after('marketing_emails_enabled');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'language',
                'timezone',
                'email_notifications_enabled',
                'push_notifications_enabled',
                'marketing_emails_enabled',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ]);
        });
    }
};
