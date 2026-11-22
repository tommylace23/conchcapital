<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Get transaction history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'type' => 'nullable|in:deposit,withdrawal,credit,debit',
            'fund_type' => 'nullable|in:real,simulated',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->transactions()->latest();

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['fund_type'])) {
            $query->where('fund_type', $validated['fund_type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Get a single transaction
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $transaction = $user->transactions()->findOrFail($id);

        return response()->json([
            'transaction' => $transaction,
        ]);
    }

    /**
     * Create a deposit transaction
     */
    public function deposit(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'currency' => 'nullable|string|in:USD,BTC,USDT,ETH',
            'crypto_currency' => 'nullable|string|in:BTC,USDT,ETH',
            'fund_type' => 'required|in:real,simulated',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        // Check if account is frozen
        if ($user->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Cannot process transactions.',
            ], 403);
        }

        return DB::transaction(function () use ($user, $validated) {
            $cryptoCurrency = $validated['crypto_currency'] ?? 'BTC';
            $usdAmount = $validated['amount'];
            
            // Get current crypto exchange rates (in production, use a real API)
            $exchangeRates = $this->getCryptoExchangeRates();
            $cryptoAmount = $usdAmount / ($exchangeRates[$cryptoCurrency] ?? 1);
            
            // Get Trezor-secured address for this crypto currency
            $cryptoAddress = $this->getTrezorAddress($cryptoCurrency);
            
            // Create transaction with pending status (will be completed when crypto is received)
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'fund_type' => $validated['fund_type'],
                'amount' => $usdAmount,
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'pending', // Pending until crypto is received
                'description' => $validated['description'] ?? 'Deposit',
                'reference' => 'DEP-' . strtoupper(uniqid()),
                'metadata' => array_merge($validated['metadata'] ?? [], [
                    'crypto_currency' => $cryptoCurrency,
                    'crypto_amount' => $cryptoAmount,
                    'crypto_address' => $cryptoAddress,
                    'exchange_rate' => $exchangeRates[$cryptoCurrency] ?? null,
                ]),
            ]);

            // Don't update balance yet - wait for crypto confirmation
            // Balance will be updated when admin confirms the deposit

            // Don't create activity log yet - only create when admin confirms the deposit
            // This prevents pending deposits from showing in recent activity

            return response()->json([
                'transaction' => $transaction->fresh(),
                'user' => $user->fresh(),
                'crypto_address' => $cryptoAddress,
                'crypto_amount' => $cryptoAmount,
                'crypto_currency' => $cryptoCurrency,
                'usd_amount' => $usdAmount,
            ], 201);
        });
    }

    /**
     * Get current crypto exchange rates
     * In production, integrate with a real exchange rate API (CoinGecko, Coinbase, etc.)
     */
    private function getCryptoExchangeRates()
    {
        // For now, return approximate rates
        // In production, fetch from an API like CoinGecko
        return [
            'BTC' => 45000, // $45,000 per BTC
            'USDT' => 1,    // 1:1 with USD
            'ETH' => 2500,  // $2,500 per ETH
        ];
    }

    /**
     * Get Trezor-secured crypto address for deposits
     * 
     * These addresses are generated from your Trezor hardware wallet.
     * To get your Trezor addresses:
     * 1. Connect your Trezor device
     * 2. Open Trezor Suite
     * 3. Navigate to the account for each cryptocurrency
     * 4. Copy the receiving address
     * 5. Add to .env file: TREZOR_BTC_ADDRESS, TREZOR_ETH_ADDRESS, TREZOR_USDT_ADDRESS
     * 
     * IMPORTANT: The private keys for these addresses are stored on your Trezor device.
     * Only you (with physical access to the Trezor) can access these funds.
     */
    private function getTrezorAddress($cryptoCurrency)
    {
        // Trezor-secured addresses from your hardware wallet
        // Store these in .env file for security
        $addresses = [
            'BTC' => env('TREZOR_BTC_ADDRESS', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh'),
            'ETH' => env('TREZOR_ETH_ADDRESS', '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'),
            'USDT' => env('TREZOR_USDT_ADDRESS', '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'), // USDT on Ethereum network (ERC-20)
        ];
        
        return $addresses[$cryptoCurrency] ?? $addresses['BTC'];
    }

    /**
     * Create a withdrawal transaction
     */
    public function withdrawal(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        // Check if account is frozen
        if ($user->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Cannot process transactions.',
            ], 403);
        }

        // Only real funds can be withdrawn
        if ($user->real_balance < $validated['amount']) {
            return response()->json([
                'message' => 'Insufficient balance for withdrawal.',
            ], 422);
        }

        return DB::transaction(function () use ($user, $validated) {
            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'fund_type' => 'real', // Only real funds can be withdrawn
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'pending', // Withdrawals need approval/processing
                'description' => $validated['description'] ?? 'Withdrawal',
                'reference' => 'WD-' . strtoupper(uniqid()),
                'metadata' => $validated['metadata'] ?? [],
            ]);

            // Deduct from balance (even if pending, reserve the funds)
            $user->decrement('real_balance', $validated['amount']);
            $user->save();

            // Create activity log
            $currency = $validated['currency'] ?? 'USD';
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'withdrawal',
                'fund_type' => 'real',
                'entity_type' => Transaction::class,
                'entity_id' => $transaction->id,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'description' => "Withdrawal request for {$validated['amount']} {$currency}",
            ]);

            return response()->json([
                'transaction' => $transaction->fresh(),
                'user' => $user->fresh(),
            ], 201);
        });
    }
}
