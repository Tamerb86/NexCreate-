<?php

namespace App\Domain\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Auth\Requests\RegisterRequest;
use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        // Get the role
        $role = Role::where('name', $request->role)->first();

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role specified.',
            ], 400);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
        ]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load the role relationship
        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'bio' => $user->bio,
                    'city' => $user->city,
                    'country' => $user->country,
                    'role' => $user->role->name,
                    'created_at' => $user->created_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }
}
