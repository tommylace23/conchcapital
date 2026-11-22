<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BankAccount;
use App\Support\BankSettings;
use Illuminate\Http\Request;

class BankController extends Controller
{
    /**
     * Banking details for the signed-in client, including their own account.
     */
    public function details(Request $request)
    {
        $user = $request->user();
        $settings = BankSettings::all();

        $number = $user->account_number ?: '';
        $iban = $user->account_iban ?: ($number ? BankAccount::toIban($number) : '');
        $prefix = $settings['deposit_reference_prefix'] ?: 'DEP';

        return response()->json([
            'bank' => [
                'name' => $settings['bank_name'],
                'address' => $settings['bank_address'],
                'swift' => $settings['bank_swift'],
                'routing' => $settings['bank_routing'],
                'account_holder' => $settings['bank_account_holder'],
                'support_email' => $settings['bank_support_email'],
                'support_phone' => $settings['bank_support_phone'],
            ],
            'client' => [
                'account_number' => $number,
                'account_number_formatted' => $number ? BankAccount::format($number) : '',
                'iban' => $iban,
                'iban_formatted' => $iban ? BankAccount::formatIban($iban) : '',
                'issued_at' => $user->account_issued_at?->toIso8601String(),
                'currency' => $user->balance_currency ?? 'USD',
            ],
            'deposit_reference' => $number ? $prefix . '-' . $number : null,
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
