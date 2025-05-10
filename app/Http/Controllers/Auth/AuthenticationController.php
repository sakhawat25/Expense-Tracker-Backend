<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'hcaptcha_token' => 'required',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Invalid email address',
            'password.required' => 'Password is required',
            'hcaptcha_token.required' => 'Captcha is required',
        ]);

        if (! $this->verifyHcaptcha($request->hcaptcha_token)) {
            return response()->json([
                'errors' => [
                    'hcaptcha_token' => ['Captcha verification failed. Please try again.']
                ]
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if email is verified
        if (! $user->hasVerifiedEmail()) {
            event(new Registered($user));

            return $this->errorResponse([
                'message' => 'Your login attempt was unsuccessful. Please check your credentials or verify your email.',
                'unverified' => true,
            ], 'Email address is not verified.', 403);
        }

        $token =  $user->createToken($user->email)->plainTextToken;

        return $this->successResponse(['token' => $token], 'Login successful!');
    }

    public function register(Request $request, CreatesNewUsers $creator)
    {
        // Validate and create user
        event(new Registered($user = $creator->create($request->all())));

        // Laravel will automatically send email verification if needed
        return response()->json([
            'message' => 'Registered successfully. Please check your email to verify your account.',
        ], 201);
    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete();

        return $this->successResponse(message: 'Logout successful.');
    }
}
