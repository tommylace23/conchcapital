<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\SimulatedTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BankSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOpsController extends Controller
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
     * Paginated admin action audit log.
     */
    public function actionsLog(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'action' => 'nullable|string|max:64',
            'admin_id' => 'nullable|integer',
            'target_user_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = AdminAction::with([
            'admin:id,username,display_name,name',
        ]);

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }
        if (! empty($validated['admin_id'])) {
            $query->where('admin_id', $validated['admin_id']);
        }
        if (! empty($validated['target_user_id'])) {
            $query->where('target_user_id', $validated['target_user_id']);
        }

        $logs = $query->latest()->paginate($validated['per_page'] ?? 25);

        $actions = AdminAction::select('action')->distinct()->orderBy('action')->pluck('action');

        return response()->json([
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
            ],
            'actions' => $actions,
        ]);
    }

    /**
     * Simulated transfers across the platform.
     */
    public function simulatedTransfers(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = SimulatedTransfer::with([
            'fromUser:id,username,display_name,name',
            'toUser:id,username,display_name,name',
        ]);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $transfers = $query->latest()->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'transfers' => $transfers->items(),
            'pagination' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
                'per_page' => $transfers->perPage(),
            ],
        ]);
    }

    /**
     * Currently frozen accounts.
     */
    public function frozenAccounts(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $users = User::where('is_frozen', true)
            ->select(['id', 'username', 'display_name', 'name', 'email', 'real_balance', 'frozen_at', 'freeze_reason'])
            ->latest('frozen_at')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ],
        ]);
    }

    /**
     * CSV compliance export of transactions in a date range.
     */
    public function complianceExport(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:deposit,withdrawal,credit,debit,growth',
            'format' => 'nullable|in:csv,json',
        ]);

        $start = isset($validated['start_date']) ? \Carbon\Carbon::parse($validated['start_date'])->startOfDay() : now()->subDays(30)->startOfDay();
        $end = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date'])->endOfDay() : now()->endOfDay();

        $query = Transaction::with('user:id,username,display_name,name,email')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at');

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $format = $validated['format'] ?? 'csv';
        $filename = 'compliance-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.' . $format;

        if ($format === 'json') {
            $rows = $query->get()->map(fn ($t) => [
                'id' => $t->id,
                'date' => $t->created_at?->toIso8601String(),
                'username' => $t->user?->username,
                'email' => $t->user?->email,
                'type' => $t->type,
                'fund_type' => $t->fund_type,
                'amount' => $t->amount,
                'currency' => $t->currency,
                'status' => $t->status,
                'reference' => $t->reference,
                'description' => $t->description,
            ]);

            return response()->streamDownload(function () use ($rows) {
                echo json_encode($rows, JSON_PRETTY_PRINT);
            }, $filename, ['Content-Type' => 'application/json']);
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Transaction ID', 'Date', 'Username', 'Email', 'Type', 'Fund Type', 'Amount', 'Currency', 'Status', 'Reference', 'Description'], ',', '"', '\\');

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $t) {
                    fputcsv($out, [
                        $t->id,
                        $t->created_at?->toIso8601String(),
                        $t->user?->username,
                        $t->user?->email,
                        $t->type,
                        $t->fund_type,
                        $t->amount,
                        $t->currency,
                        $t->status,
                        $t->reference,
                        $t->description,
                    ], ',', '"', '\\');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Platform settings (generic key/value).
     */
    public function getPlatformSettings(Request $request)
    {
        $this->ensureAdmin($request);

        $keys = ['support_email', 'support_phone', 'maintenance_mode', 'maintenance_message', 'min_deposit', 'max_withdrawal'];

        $stored = DB::table('platform_settings')->whereIn('key', $keys)->pluck('value', 'key');

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $stored[$key] ?? null;
        }

        return response()->json([
            'settings' => $settings,
            'keys' => $keys,
            'bank' => BankSettings::all(),
        ]);
    }

    /**
     * Save platform settings (empty string clears a value).
     */
    public function updatePlatformSettings(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $keys = ['support_email', 'support_phone', 'maintenance_mode', 'maintenance_message', 'min_deposit', 'max_withdrawal'];

        $rules = [];
        foreach ($keys as $key) {
            $rules[$key] = 'nullable|string|max:255';
        }
        $rules['support_email'] = 'nullable|email|max:255';
        $rules['min_deposit'] = 'nullable|numeric|min:0';
        $rules['max_withdrawal'] = 'nullable|numeric|min:0';

        $validated = $request->validate($rules);

        foreach ($keys as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = $validated[$key];
            if ($value === null || $value === '') {
                DB::table('platform_settings')->where('key', $key)->delete();
                continue;
            }

            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        AdminAction::create([
            'admin_id' => $admin->id,
            'action' => 'update_platform_settings',
            'entity_type' => 'platform_settings',
            'description' => 'Updated platform settings',
            'metadata' => ['keys' => array_keys($validated)],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Platform settings saved.']);
    }

    /**
     * Profit growth accounts belonging to a specific user (admin view).
     */
    public function userProfitGrowths(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $growths = \App\Models\ProfitGrowth::where('user_id', $id)
            ->latest()
            ->get();

        return response()->json(['profit_growths' => $growths]);
    }

    /**
     * A single profit growth (admin view, any owner).
     */
    public function profitGrowth(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $growth = \App\Models\ProfitGrowth::with('user:id,username,display_name,name')->findOrFail($id);

        return response()->json(['profit_growth' => $growth]);
    }

    /**
     * List admin accounts.
     */
    public function admins(Request $request)
    {
        $this->ensureAdmin($request);

        $admins = User::where('is_admin', true)
            ->select(['id', 'username', 'display_name', 'name', 'email', 'last_login_at', 'created_at'])
            ->orderBy('id')
            ->get();

        return response()->json(['admins' => $admins]);
    }

    /**
     * Create an admin account.
     */
    public function createAdmin(Request $request)
    {
        $actor = $this->ensureAdmin($request);

        // Single-admin mode: one operator runs the platform.
        if (User::where('is_admin', true)->exists()) {
            return response()->json([
                'message' => 'An administrator already exists. This platform runs in single-admin mode.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'username' => ['nullable', 'regex:/^[A-Za-z0-9._-]+$/', 'min:3', 'max:20', 'unique:users,username'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'display_name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? ('admin' . random_int(1000, 9999)),
            'password' => $validated['password'],
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        AdminAction::create([
            'admin_id' => $actor->id,
            'action' => 'create_admin',
            'target_user_id' => $user->id,
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'description' => "Created admin {$user->email}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Admin created.', 'admin' => $user], 201);
    }

    /**
     * Bulk freeze / unfreeze.
     */
    public function bulkUsers(Request $request)
    {
        $actor = $this->ensureAdmin($request);

        $validated = $request->validate([
            'action' => 'required|in:freeze,unfreeze',
            'user_ids' => 'required|array|min:1|max:200',
            'user_ids.*' => 'integer|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])
            ->where('id', '!=', $actor->id)
            ->get();

        $changed = 0;

        DB::transaction(function () use ($users, $validated, $actor, &$changed) {
            foreach ($users as $user) {
                if ($validated['action'] === 'freeze') {
                    if ($user->is_frozen) {
                        continue;
                    }
                    $user->forceFill([
                        'is_frozen' => true,
                        'frozen_at' => now(),
                        'freeze_reason' => $validated['reason'] ?? 'Bulk freeze',
                    ])->save();
                } else {
                    if (! $user->is_frozen) {
                        continue;
                    }
                    $user->forceFill([
                        'is_frozen' => false,
                        'frozen_at' => null,
                        'freeze_reason' => null,
                    ])->save();
                }
                $changed++;
            }

            AdminAction::create([
                'admin_id' => $actor->id,
                'action' => 'bulk_' . $validated['action'],
                'entity_type' => User::class,
                'description' => "Bulk {$validated['action']} on {$changed} user(s)",
                'metadata' => ['user_ids' => $validated['user_ids'], 'changed' => $changed],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return response()->json([
            'message' => "{$changed} account(s) updated.",
            'changed' => $changed,
        ]);
    }

    /**
     * Create a client account from the admin panel.
     */
    public function createUser(Request $request)
    {
        $actor = $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email:rfc,dns|max:255|unique:users,email',
            'username' => ['nullable', 'regex:/^[A-Za-z0-9._-]+$/', 'min:3', 'max:20', 'unique:users,username'],
            'password' => 'required|string|min:8',
            'real_balance' => 'nullable|numeric|min:0',
            'is_frozen' => 'nullable|boolean',
        ], [
            'username.regex' => 'Usernames may only contain letters, numbers, dots, hyphens and underscores.',
            'username.unique' => 'That username is already taken.',
            'email.unique' => 'That email is already registered.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'display_name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'password' => $validated['password'],
            'is_admin' => false,
            'is_frozen' => (bool) ($validated['is_frozen'] ?? false),
            'email_verified_at' => now(),
            'real_balance' => $validated['real_balance'] ?? 0,
        ]);

        AdminAction::create([
            'admin_id' => $actor->id,
            'action' => 'create_user',
            'target_user_id' => $user->id,
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'description' => "Created client {$user->email}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'User created.',
            'user' => $user->fresh(),
        ], 201);
    }
}
