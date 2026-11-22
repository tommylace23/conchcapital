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
        // Modify the enum to include 'test' (5 minutes for testing)
        // MySQL requires dropping and recreating the column to modify enum
        DB::statement("ALTER TABLE profit_growths MODIFY COLUMN growth_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'test') DEFAULT 'daily'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'test' from enum
        DB::statement("ALTER TABLE profit_growths MODIFY COLUMN growth_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'daily'");
    }
};
