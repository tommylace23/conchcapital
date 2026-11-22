<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnsureAdmin extends Command
{
    protected $signature = 'app:ensure-admin';

    protected $description = 'Ensure a single admin account exists (reads ADMIN_EMAIL / ADMIN_PASSWORD)';

    public function handle(): int
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping admin seed.');

            return self::SUCCESS;
        }

        $admin = User::where('is_admin', true)->first() ?? User::where('email', $email)->first();

        if ($admin) {
            $admin->forceFill([
                'name' => $admin->name ?: 'Administrator',
                'display_name' => $admin->display_name ?: 'Administrator',
                'email' => $email,
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ])->save();

            $this->info("Admin ensured: {$admin->email} (id {$admin->id})");

            return self::SUCCESS;
        }

        $admin = User::create([
            'name' => 'Administrator',
            'display_name' => 'Administrator',
            'email' => $email,
            'username' => 'admin',
            'password' => $password,
            'is_admin' => true,
            'is_frozen' => false,
            'email_verified_at' => now(),
        ]);

        $this->info("Admin created: {$admin->email} (id {$admin->id})");

        return self::SUCCESS;
    }
}
