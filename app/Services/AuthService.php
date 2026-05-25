<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Неверный email или пароль',
            ], 404);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'message' => 'Аккаунт деактивирован. Обратитесь к администратору.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        $user->tokens()->delete();

        $token = $user->createToken(
            name: $request->input('device_name', 'api-token'),
            expiresAt: now()->addDays(30),
        )->plainTextToken;

        return response()->json([
            'message' => 'Успешный вход',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Успешный выход']);
    }

    public function me()
    {
        return response()->json([
            'user' => new UserResource(auth()->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        if (array_key_exists('first_name', $validated)) {
            $validated['name'] = $validated['first_name'];
            unset($validated['first_name']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Профиль успешно обновлен',
            'user'    => new UserResource($user->refresh())
        ], 200);

    }
}
