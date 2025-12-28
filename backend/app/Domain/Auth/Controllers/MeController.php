<?php

namespace App\Domain\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Get the authenticated user's data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Load relationships
        $user->load(['role', 'socialLinks']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'bio' => $user->bio,
                    'city' => $user->city,
                    'country' => $user->country,
                    'role' => $user->role->name,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'social_links' => $user->socialLinks->map(function ($link) {
                        return [
                            'id' => $link->id,
                            'provider' => $link->provider,
                            'url' => $link->url,
                            'username' => $link->username,
                            'followers_count' => $link->followers_count,
                        ];
                    }),
                ],
            ],
        ], 200);
    }
}
