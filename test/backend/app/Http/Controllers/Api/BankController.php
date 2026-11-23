<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BankSettings;
use Illuminate\Http\Request;

class BankController extends Controller
{
    /**
     * Deposit banking details shared by all clients, plus the client's
     * personal deposit reference (used to match incoming transfers).
     */
    public function details(Request $request)
    {
        $user = $request->user();
        $settings = BankSettings::all();

        $prefix = $settings['deposit_reference_prefix'] ?: 'DEP';
        $reference = $prefix . '-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

        return response()->json([
            'bank' => [
                'name' => $settings['bank_name'],
                'address' => $settings['bank_address'],
                'swift' => $settings['bank_swift'],
                'routing' => $settings['bank_routing'],
                'account_holder' => $settings['bank_account_holder'],
                'support_phone' => $settings['bank_support_phone'],
            ],
            'client' => [
                'name' => $user->display_name ?: $user->name,
                'currency' => $user->balance_currency ?? 'USD',
            ],
            'deposit_reference' => $reference,
            'relationship_manager' => [
                'name' => $settings['rm_name'],
                'title' => $settings['rm_title'],
                'email' => $settings['rm_email'],
                'phone' => $settings['rm_phone'],
                'booking_url' => $settings['rm_booking_url'],
            ],
        ]);
    }
}
