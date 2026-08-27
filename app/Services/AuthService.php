<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserDetailsResource;
use App\Http\Resources\UserResource;
use App\Models\Lesson;
use App\Models\UserCertificate;
use App\Models\UserCourse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

    public function main()
    {
        $userId = auth()->id();

        return Cache::remember("user_dashboard_{$userId}", 300, function () use ($userId) {
            $user = auth()->user()->load('department');

            $courseStats = UserCourse::where('user_id', $userId)
                ->selectRaw("
                COUNT(CASE WHEN status != 'completed' THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
            ")
                ->first();

            $certificatesCount = UserCertificate::where('user_id', $userId)->count();

            $lastUserCourse = UserCourse::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->with('course:id,name,slug')
                ->latest('updated_at')
                ->first();

            $nextLesson = null;

            if ($lastUserCourse && $lastUserCourse->course) {
                $nextLesson = $lastUserCourse->course->getCurrentLesson();
            }

            return new UserDetailsResource([
                'user' => $user,
                'stats' => [
                    'active_courses_count'    => (int) ($courseStats->active_count ?? 0),
                    'completed_courses_count' => (int) ($courseStats->completed_count ?? 0),
                    'certificates_count'      => $certificatesCount,
                ],
                'last_course' => $lastUserCourse?->course,
                'next_lesson' => $nextLesson,
            ]);
        });
    }
}
