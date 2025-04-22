<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'hcaptcha_token' => 'required',
        ], [
            'hcaptcha_token.required' => 'Captcha is required',
        ]);

        if (! $this->verifyHcaptcha($request->hcaptcha_token)) {
            return response()->json([
                'errors' => [
                    'hcaptcha_token' => ['Captcha verification failed. Please try again.']
                ]
            ], 422);
        }

        $validatedData['password'] = Hash::make($request->password);

        $user = User::create($validatedData);

        event(new Registered($user));

        return $this->successResponse(
            message: 'Registration successful, please check your email for verification'
        );
    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete();

        return $this->successResponse(message: 'Logout successful.');
    }
}
