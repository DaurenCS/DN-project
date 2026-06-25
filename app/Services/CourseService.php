<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Exception;

class CourseService
{
    public function getCourses(int $perPage = 10)
    {
        return Course::query()
            ->withCount(['modules', 'lessons'])
            ->where('is_active', true)
            ->withAuthUserProgress()
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getCourse(string $slug): Course
    {
        return Course::query()
            ->withAuthUserProgress()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['modules', 'lessons'])
            ->with(['modules.lessons' => function ($query) {
                $query->withExists('currentAuthProgress');
            }])
            ->firstOrFail();
    }

    public function getUserCourses(User $user)
    {
        return $user->courses()
            ->where('is_active', true)
            ->withCount(['modules', 'lessons'])
            ->get();
    }

    public function start(User $user, string $slug): void
    {
        $course = Course::query()->where('slug', $slug)->firstOrFail();

        $userCourse = UserCourse::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$userCourse) {
            throw new Exception('Вы не записаны на этот курс', 403);
        }

        if ($userCourse->start_date) {
            throw new Exception('Курс уже начат', 400);
        }

        $userCourse->update([
            'start_date' => now(),
        ]);
    }

    public function finish(User $user, string $slug): void
    {
        $course = Course::query()->where('slug', $slug)->firstOrFail();

        $this->completeCourse($user, $course);
    }

    public function completeCourse(User $user, Course $course): void
    {
        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        if ($userCourse->progress < 100) {
            throw new Exception('Курс еще не пройден полностью.', 400);
        }

        $userCourse->update([
            'end_date' => now()
        ]);
    }
}
