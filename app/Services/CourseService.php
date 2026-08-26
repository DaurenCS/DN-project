<?php

namespace App\Services;

use App\Interfaces\CertificateGeneratorInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function __construct(
        protected CertificateGeneratorInterface $certificateGenerator,
        protected LessonService $lessonService
    ) {}

    public function getCourses(int $perPage = 10)
    {
        return Course::query()
            ->withCount(['modules', 'lessons'])
            ->where('is_active', true)
            ->withAuthUserProgress()
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getCourse(string $slug, ?User $user = null): Course
    {
        $user = $user ?? Auth::guard('sanctum')->user();

        $course = Course::query()
            ->withAuthUserProgress()
            ->withCurrentLessonData()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['modules', 'lessons'])
            ->firstOr(fn () => abort(404, 'Данный курс не найден'));

        $this->lessonService->calculateAccessForCourse($course, $user, $course->allLessons());

        return $course;
    }

    public function getUserCourses(User $user): Collection
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
            ->firstOr(fn () => abort(403, 'Вы не записаны на этот курс'));

        if ($userCourse->start_date) {
            abort(400, 'Курс уже начат');
        }

        $userCourse->update([
            'start_date' => now(),
            'status'     => 'continue',
        ]);
    }

    public function finish(User $user, string $slug): void
    {
        $userCourse = UserCourse::query()
            ->where('user_id', $user->id)
            ->whereHas('course', fn ($q) => $q->where('slug', $slug)->where('is_active', true))
            ->with('course')
            ->firstOr(fn () => abort(404, 'Запись на курс не найдена'));

        $this->completeCourse($userCourse, $user);
    }

    public function completeCourse(UserCourse $userCourse, ?User $user = null): void
    {
        if ($userCourse->progress < 100) {
            abort(400, 'Курс еще не пройден полностью.');
        }

        $user = $user ?? $userCourse->user;

        DB::transaction(function () use ($userCourse) {
            $userCourse->update([
                'end_date' => now(),
                'status'   => 'completed',
            ]);
        });

        $this->certificateGenerator->requestCertificateForCourse(
            $user,
            $userCourse->course->slug
        );
    }

    private function findActiveCourseBySlug(string $slug): Course
    {
        return Course::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOr(fn () => abort(404, 'Данный курс не найден'));
    }
}
