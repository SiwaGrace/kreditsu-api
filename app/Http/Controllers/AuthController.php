<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Registration method
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        // for token test
        if ($request->hasSession()) {
        $request->session()->regenerate();
    }
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user'    => $user->only('id', 'name', 'email'),
            'token'   => $token,
        ], 201);
    }

    // Login method
    public function login(Request $request)
{
    $validated = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    
    if (!Auth::attempt($validated)) {
        return response()->json([
            'message' => 'Invalid credentials'
            ], 401);
            }

    if ($request->hasSession()) {
        $request->session()->regenerate();
    }

            $token = Auth::user()->createToken('auth-token')->plainTextToken;


    return response()->json([
        'message' => 'Login successful',
        'token'   => $token,
        'user'    => Auth::user()->only('id', 'name', 'email'),
    ]);
}

// Logout method
public function logout(Request $request)
{
    $token = $request->user()->currentAccessToken();

    // only delete if it's a real token (Bearer), not a session (TransientToken)
    if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
        $token->delete();
    }

    Auth::guard('web')->logout();

    // keep
    if ($request->hasSession()) {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}

// Get authenticated user details
public function me(Request $request)
    {
        return response()->json(
            $request->user()->only('id', 'name', 'email')  // ← $request->user() not Auth::user()
        );
    }

    // Update authenticated user
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'confirmed', Password::min(8)],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user->only('id', 'name', 'email'),
        ]);
    }
}