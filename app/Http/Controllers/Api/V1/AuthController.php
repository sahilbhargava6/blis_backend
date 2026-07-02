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
     * Validate a tokenized invitation
     */
    public function validateInvitation($token)
    {
        $invitation = \App\Models\GroupInvitation::where('token', $token)
            ->where('is_used', false)
            ->where('expires_at', '>', \Carbon\Carbon::now())
            ->with('group.leader')
            ->first();

        if (!$invitation) {
            return $this->errorResponse('Invalid, used, or expired invitation token.', 404);
        }

        return $this->successResponse($invitation, 'Invitation token is valid.');
    }

    /**
     * Register a new user (Member or Leader)
     */
    public function register(RegisterRequest $request)
    {
        $groupId = null;
        $invitation = null;

        if ($request->filled('invite_token')) {
            $invitation = \App\Models\GroupInvitation::where('token', $request->invite_token)
                ->where('is_used', false)
                ->where('expires_at', '>', \Carbon\Carbon::now())
                ->first();

            if (!$invitation) {
                return $this->errorResponse('Invalid, used, or expired invitation token.', 422);
            }

            $groupId = $invitation->group_id;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $groupId ? 'member' : $request->role,
            'group_id' => $groupId,
            'niche_field' => $request->niche_field,
        ]);

        if ($invitation) {
            $invitation->update(['is_used' => true]);
        }

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
