<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfileViewRequestController;
use App\Http\Controllers\Api\ProfitGrowthController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SimulatedTransferController;
use App\Http\Controllers\Api\SimulationController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);

// Public (unauthenticated) social proof feed
Route::get('activity/public', [\App\Http\Controllers\Api\PublicActivityController::class, 'recentPayouts']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('verify-2fa-login', [AuthController::class, 'verify2FALogin'])->middleware('throttle:15,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('resend-verification', [AuthController::class, 'sendEmailVerification']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Referrals
    Route::get('referrals', [\App\Http\Controllers\Api\ReferralController::class, 'index']);

    // Impersonation (stop/status work while impersonating, so not admin-guarded)
    Route::post('impersonation/stop', [\App\Http\Controllers\Api\ImpersonationController::class, 'stop']);
    Route::get('impersonation/status', [\App\Http\Controllers\Api\ImpersonationController::class, 'status']);

    // Banking / account details
    Route::get('bank-details', [\App\Http\Controllers\Api\BankController::class, 'details']);
    
    // Profile
    Route::get('user/profile', [ProfileController::class, 'show']);
    Route::put('user/profile', [ProfileController::class, 'update']);
    Route::get('user/search', [ProfileController::class, 'searchUsers']);
    Route::get('user/profile/{id}', [ProfileController::class, 'showUser']);
    Route::get('user/profile/{id}/portfolio', [ProfileController::class, 'getUserPortfolio']);
    
    // Settings
    Route::prefix('user/settings')->group(function () {
        Route::get('/', [SettingsController::class, 'getSettings']);
        Route::put('account', [SettingsController::class, 'updateAccountSettings']);
        Route::put('notifications', [SettingsController::class, 'updateNotificationPreferences']);
        Route::post('change-password', [SettingsController::class, 'changePassword']);
        Route::post('change-email', [SettingsController::class, 'changeEmail']);
        Route::get('sessions', [SettingsController::class, 'getActiveSessions']);
        Route::delete('sessions/{sessionId}', [SettingsController::class, 'revokeSession']);
        Route::delete('sessions', [SettingsController::class, 'revokeAllOtherSessions']);
        Route::post('2fa/initialize', [SettingsController::class, 'initialize2FA']);
        Route::post('2fa/enable', [SettingsController::class, 'enable2FA']);
        Route::post('2fa/disable', [SettingsController::class, 'disable2FA']);
        Route::delete('account', [SettingsController::class, 'deleteAccount']);
    });
    
    // Profile View Requests
    Route::prefix('profile-view-requests')->group(function () {
        Route::post('create', [ProfileViewRequestController::class, 'create']);
        Route::get('pending', [ProfileViewRequestController::class, 'getPending']);
        Route::post('{id}/approve', [ProfileViewRequestController::class, 'approve']);
        Route::post('{id}/decline', [ProfileViewRequestController::class, 'decline']);
        Route::get('check-access/{profileOwnerId}', [ProfileViewRequestController::class, 'checkAccess']);
    });
    
    // Transactions
    Route::prefix('user')->group(function () {
        Route::get('transactions', [TransactionController::class, 'history']);
        Route::get('transactions/{id}', [TransactionController::class, 'show']);
        Route::post('transactions/deposit', [TransactionController::class, 'deposit']);
        Route::post('transactions/withdrawal', [TransactionController::class, 'withdrawal']);
    });
    
    // Profit Growths (new)
    Route::prefix('user')->group(function () {
        Route::get('profit-growths', [ProfitGrowthController::class, 'index']);
        Route::get('profit-growths/{id}', [ProfitGrowthController::class, 'show']);
        // Users can no longer create profit growths - only admins can
        // Route::post('profit-growths', [ProfitGrowthController::class, 'store']);
        Route::put('profit-growths/{id}', [ProfitGrowthController::class, 'update']);
        Route::delete('profit-growths/{id}', [ProfitGrowthController::class, 'destroy']);
        Route::post('profit-growths/transfer-funds', [ProfitGrowthController::class, 'transferFundsWithCreate']); // Auto-creates if needed
        Route::post('profit-growths/{id}/transfer-funds', [ProfitGrowthController::class, 'transferFunds']); // Transfer to existing
        Route::post('profit-growths/{id}/withdraw-funds', [ProfitGrowthController::class, 'withdrawFunds']);
    });
    
    // Simulations (legacy - keeping for backward compatibility during transition)
    Route::prefix('user')->group(function () {
        Route::get('simulations', [SimulationController::class, 'index']);
        Route::get('simulations/{id}', [SimulationController::class, 'show']);
        Route::post('simulations', [SimulationController::class, 'store']);
        Route::put('simulations/{id}', [SimulationController::class, 'update']);
        Route::delete('simulations/{id}', [SimulationController::class, 'destroy']);
    });
    
    // Simulated Transfers
    Route::prefix('user')->group(function () {
        Route::get('simulated-transfers', [SimulatedTransferController::class, 'index']);
        Route::get('simulated-transfers/sent', [SimulatedTransferController::class, 'sent']);
        Route::get('simulated-transfers/received', [SimulatedTransferController::class, 'received']);
        Route::get('simulated-transfers/{id}', [SimulatedTransferController::class, 'show']);
        Route::post('simulated-transfers', [SimulatedTransferController::class, 'store']);
    });
    
    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('users', [AdminController::class, 'getUsers']);
        Route::get('users/{id}', [AdminController::class, 'getUser']);
        Route::post('credits', [AdminController::class, 'creditFunds']);
        Route::post('users/{id}/freeze', [AdminController::class, 'freezeAccount']);
        Route::post('users/{id}/unfreeze', [AdminController::class, 'unfreezeAccount']);
        Route::post('users/{id}/referral-bonus', [AdminController::class, 'setReferralBonus']);
        Route::post('users/{id}/impersonate', [\App\Http\Controllers\Api\ImpersonationController::class, 'start']);

        // Client-facing bank details
        Route::get('bank-settings', [\App\Http\Controllers\Api\AdminBankSettingsController::class, 'index']);
        Route::put('bank-settings', [\App\Http\Controllers\Api\AdminBankSettingsController::class, 'update']);
        Route::post('bank-settings/reset', [\App\Http\Controllers\Api\AdminBankSettingsController::class, 'reset']);

        // Ops: audit log, simulated transfers, frozen accounts, exports, platform settings
        Route::get('actions-log', [\App\Http\Controllers\Api\AdminOpsController::class, 'actionsLog']);
        Route::get('simulated-transfers', [\App\Http\Controllers\Api\AdminOpsController::class, 'simulatedTransfers']);
        Route::get('frozen-accounts', [\App\Http\Controllers\Api\AdminOpsController::class, 'frozenAccounts']);
        Route::get('compliance-export', [\App\Http\Controllers\Api\AdminOpsController::class, 'complianceExport']);
        Route::get('platform-settings', [\App\Http\Controllers\Api\AdminOpsController::class, 'getPlatformSettings']);
        Route::get('users/{id}/profit-growths', [\App\Http\Controllers\Api\AdminOpsController::class, 'userProfitGrowths']);
        Route::post('users/bulk', [\App\Http\Controllers\Api\AdminOpsController::class, 'bulkUsers']);
        Route::post('users', [\App\Http\Controllers\Api\AdminOpsController::class, 'createUser']);
        Route::get('admins', [\App\Http\Controllers\Api\AdminOpsController::class, 'admins']);
        Route::post('admins', [\App\Http\Controllers\Api\AdminOpsController::class, 'createAdmin']);
        Route::get('profit-growths/{id}', [\App\Http\Controllers\Api\AdminOpsController::class, 'profitGrowth']);
        Route::put('platform-settings', [\App\Http\Controllers\Api\AdminOpsController::class, 'updatePlatformSettings']);
        Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('transactions', [AdminController::class, 'getTransactions']);
        Route::get('reports', [AdminController::class, 'getReports']);
        // Profit Growth management
        Route::post('profit-growths', [AdminController::class, 'createProfitGrowth']);
        Route::put('profit-growths/{id}', [AdminController::class, 'updateProfitGrowth']); // Admin can update any profit growth
        Route::post('profit-growths/calculate-growth', [AdminController::class, 'triggerGrowthCalculation']);
        Route::delete('profit-growths/{id}', [AdminController::class, 'deleteProfitGrowth']);
        // Deposit confirmation
        Route::get('deposits/pending', [AdminController::class, 'getPendingDeposits']);
        Route::post('deposits/{id}/confirm', [AdminController::class, 'confirmDeposit']);
        Route::post('deposits/{id}/check', [AdminController::class, 'checkDepositStatus']);
        // Withdrawal management
        Route::get('withdrawals/pending', [AdminController::class, 'getPendingWithdrawals']);
        Route::post('withdrawals', [AdminController::class, 'createWithdrawal']);
        Route::post('withdrawals/{id}/confirm', [AdminController::class, 'confirmWithdrawal']);
        Route::post('withdrawals/{id}/cancel', [AdminController::class, 'cancelWithdrawal']);
        // Activity log cleanup
        Route::post('activity-logs/cleanup-pending-deposits', [AdminController::class, 'cleanupPendingDepositActivityLogs']);
        // User profit growth check
        Route::get('users/{id}/profit-growth-check', [AdminController::class, 'checkUserProfitGrowth']);
    });
});
