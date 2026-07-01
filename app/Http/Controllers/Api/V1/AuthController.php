<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user (Member or Leader)
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'niche_field' => $request->niche_field,
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token
        ], 'User registered successfully.', 201);
    }

    /**
     * Authenticate a user and return a Sanctum token
     */
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('api_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token
        ], 'Logged in successfully.');
    }

    /**
     * Revoke the current active API token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully.');
    }

    /**
     * Fetch authenticated user details and wallet status
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('group.leader');

        return $this->successResponse([
            'user' => $user,
            'wallet' => [
                'pending_balance' => $user->pending_balance,
                'cleared_balance' => $user->cleared_balance,
            ]
        ], 'Profile retrieved.');
    }
}
