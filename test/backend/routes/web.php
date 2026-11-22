<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('home.html'));
});

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
    $user = \App\Models\User::find($id);
    
    if (!$user) {
        return redirect('/EMAIL_VERIFICATION_PAGE.html?error=user_not_found');
    }
    
    if ($user->hasVerifiedEmail()) {
        return redirect('/EMAIL_VERIFICATION_PAGE.html?verified=1&already=1');
    }
    
    if (!hash_equals((string) $id, (string) $user->getKey())) {
        abort(403);
    }
    
    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }
    
    $user->markEmailAsVerified();

    return redirect('/EMAIL_VERIFICATION_PAGE.html?verified=1');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
    ]);

    return response()->json([
        'message' => 'Verification email endpoint is handled via API.',
    ]);
})->middleware(['throttle:6,1']);
