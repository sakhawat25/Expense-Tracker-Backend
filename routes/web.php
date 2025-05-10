<?php

use App\Http\Controllers\Auth\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthenticationController::class, 'register'])->middleware('guest');


Route::get('/email/verify/{id}/{hash}', function ($id, $hash, Request $request) {
    $user = \App\Models\User::find($id);

    if (!$user) {
        return redirect(env('FRONTEND_URL') . '/verify-email?status=user_not_found');
    }

    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return redirect(env('FRONTEND_URL') . '/verify-email?status=invalid_link');
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/verify-email?status=success');
})->middleware('signed')->name('verification.verify');


// //resend Email
// Route::post('/auth/resend-verification', function (Request $request) {
//     $user = User::where('email', $request->email)->first();

//     if (!$user) {
//         return response()->json(['message' => 'User not found.'], 404);
//     }

//     if ($user->hasVerifiedEmail()) {
//         return response()->json(['message' => 'Email already verified.'], 400);
//     }

//     $user->sendEmailVerificationNotification();
//     return response()->json(['message' => 'Verification link resent!']);
// });
