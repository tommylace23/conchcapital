<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    /**
     * Start viewing the platform as a client.
     *
     * Deliberately silent: no activity log for the user and no update to their
     * login history. Only an admin_actions row is written on the admin side.
     */
    public function start(Request $request, $id)
    {
        $admin = $request->user();

        if (! $admin || ! $admin->is_admin) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        $target = User::findOrFail($id);

        if ($target->id === $admin->id) {
            return response()->json(['message' => 'Already viewing your own account.'], 422);
        }

        $request->session()->put(self::SESSION_KEY, $admin->id);
        Auth::login($target);
        $request->session()->regenerate();

        AdminAction::create([
            'admin_id' => $admin->id,
            'action' => 'impersonate_start',
            'target_user_id' => $target->id,
            'entity_type' => User::class,
            'entity_id' => $target->id,
            'description' => "Viewing as @{$target->username}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Now viewing as client.',
            'user' => $target->fresh(),
        ]);
    }

    /**
     * Return to the admin account.
     */
    public function stop(Request $request)
    {
        $impersonatorId = $request->session()->get(self::SESSION_KEY);

        if (! $impersonatorId) {
            return response()->json(['message' => 'Not impersonating.'], 422);
        }

        $admin = User::find($impersonatorId);
        $viewed = $request->user();

        $request->session()->forget(self::SESSION_KEY);

        if (! $admin || ! $admin->is_admin) {
            Auth::logout();
            $request->session()->regenerate();

            return response()->json(['message' => 'Impersonating admin no longer exists.'], 422);
        }

        Auth::login($admin);
        $request->session()->regenerate();

        AdminAction::create([
            'admin_id' => $admin->id,
            'action' => 'impersonate_stop',
            'target_user_id' => $viewed?->id,
            'entity_type' => User::class,
            'entity_id' => $viewed?->id,
            'description' => 'Returned to admin from @' . ($viewed->username ?? 'unknown'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Returned to admin.']);
    }

    /**
     * Session state for the banner.
     */
    public function status(Request $request)
    {
        $impersonatorId = $request->session()->get(self::SESSION_KEY);
        $user = $request->user();

        if (! $impersonatorId || ! $user) {
            return response()->json(['impersonating' => false]);
        }

        $admin = User::find($impersonatorId);

        return response()->json([
            'impersonating' => true,
            'viewing_as' => [
                'id' => $user->id,
                'username' => $user->username,
                'display_name' => $user->display_name,
            ],
            'admin' => $admin ? ['id' => $admin->id, 'username' => $admin->username] : null,
        ]);
    }
}
