<?php

namespace App\Services;

use App\Interfaces\CertificateGeneratorInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Exception;
use Illuminate\Support\Facades\Auth;

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
        $course = Course::query()
            ->withAuthUserProgress()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['modules', 'lessons'])
            ->with(['modules' => function ($query) {
                $query->orderBy('order', 'asc');
            }, 'modules.lessons' => function ($query) {
                $query->withExists('currentAuthProgress')
                    ->orderBy('sort_order', 'asc');
            }])
            ->firstOr(function () {
                abort(404, 'Данный курс не найден');
            });

        $allLessons = $course->modules->flatMap(fn ($module) => $module->lessons);

        app(LessonService::class)->calculateAccessForCourse($course, Auth::guard('sanctum')->user() , $allLessons);

        return $course;
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
        $course = $this->findActiveCourseBySlug($slug);

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
            'status'     => 'continue',
        ]);
    }

    public function finish(User $user, string $slug): void
    {
        $course = $this->findActiveCourseBySlug($slug);

        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->completeCourse($userCourse);
    }

    public function completeCourse(UserCourse $userCourse, CertificateGeneratorInterface $generator): void
    {
        if ($userCourse->progress < 100) {
            throw new Exception('Курс еще не пройден полностью.', 400);
        }

        $user = Auth::guard('sanctum')->user();

        $userCourse->update([
            'end_date' => now(),
            'status'   => 'completed',
        ]);

        $generator->issueCertificateForCourse($userCourse->course()->first()->slug);
    }

    /**
     * Вспомогательный метод для поиска активного курса по slug
     */
    private function findActiveCourseBySlug(string $slug): Course
    {
        return Course::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOr(function () {
                abort(404, 'Данный курс не найден');
            });
    }
}
