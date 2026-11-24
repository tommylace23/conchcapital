<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Support\BankSettings;
use Illuminate\Http\Request;

class AdminBankSettingsController extends Controller
{
    private function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->is_admin) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        return $user;
    }

    /**
     * Current bank settings plus the config defaults (for placeholders/reset).
     */
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $defaults = [];
        foreach (BankSettings::KEYS as $key => $configPath) {
            $defaults[$key] = config($configPath);
        }

        return response()->json([
            'settings' => BankSettings::all(),
            'defaults' => $defaults,
            'keys' => array_keys(BankSettings::KEYS),
        ]);
    }

    /**
     * Save any subset of bank settings. Empty string clears an override.
     */
    public function update(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $rules = [];
        foreach (array_keys(BankSettings::KEYS) as $key) {
            $rules[$key] = 'nullable|string|max:255';
        }
        if (array_key_exists('bank_support_email', $rules)) {
            $rules['bank_support_email'] = 'nullable|email|max:255';
        }
        if (array_key_exists('rm_email', $rules)) {
            $rules['rm_email'] = 'nullable|email|max:255';
        }
        if (array_key_exists('rm_booking_url', $rules)) {
            $rules['rm_booking_url'] = 'nullable|url|max:255';
        }

        $validated = $request->validate($rules);

        // Empty values clear the override so config defaults apply.
        BankSettings::put(array_filter($validated, fn ($v) => $v !== null));

        AdminAction::create([
            'admin_id' => $admin->id,
            'action' => 'update_bank_settings',
            'entity_type' => 'platform_settings',
            'description' => 'Updated client-facing bank details',
            'metadata' => ['keys' => array_keys(array_filter($validated, fn ($v) => $v !== null))],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Banking details updated.',
            'settings' => BankSettings::all(),
        ]);
    }

    /**
     * Clear all overrides (back to env/config defaults).
     */
    public function reset(Request $request)
    {
        $this->ensureAdmin($request);
        BankSettings::reset();

        return response()->json(['message' => 'Banking details reset to defaults.']);
    }

    /**
     * Current crypto deposit addresses plus config defaults.
     */
    public function cryptoIndex(Request $request)
    {
        $this->ensureAdmin($request);

        $defaults = [];
        foreach (\App\Support\CryptoAddresses::KEYS as $key => $path) {
            $defaults[$key] = config($path);
        }

        return response()->json([
            'addresses' => \App\Support\CryptoAddresses::all(),
            'defaults' => $defaults,
        ]);
    }

    /**
     * Save crypto deposit addresses.
     */
    public function cryptoUpdate(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'btc_address' => 'nullable|string|max:120',
            'eth_address' => 'nullable|string|max:120',
            'usdt_address' => 'nullable|string|max:120',
        ]);

        \App\Support\CryptoAddresses::put(array_filter($validated, fn ($v) => $v !== null));

        AdminAction::create([
            'admin_id' => $admin->id,
            'action' => 'update_crypto_addresses',
            'entity_type' => 'platform_settings',
            'description' => 'Updated crypto deposit addresses',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Crypto addresses updated.',
            'addresses' => \App\Support\CryptoAddresses::all(),
        ]);
    }
}
