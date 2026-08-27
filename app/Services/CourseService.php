<?php

namespace App\Services;

use App\Interfaces\CertificateGeneratorInterface;
use App\Jobs\GenerateCourseCertificateJob;
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
            ->select([
                'id',
                'name',
                'description',
                'slug',
                'image',
                'is_active',
                'created_at'
            ])
            ->where('is_active', true)
            ->withAuthUserProgress()
            ->withCount(['modules', 'lessons'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getCourse(string $slug, ?User $user = null): Course
    {
        $course = Course::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withAuthUserProgress($user?->id)
            ->withCurrentLessonData()
            ->withCount(['modules', 'lessons'])
            ->firstOrFail();

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
        $course = Course::query()
            ->select('id', 'slug')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withAuthUserProgress($user->id)
            ->firstOr(fn () => abort(404, 'Данный курс не найден'));

        $userCourse = $course->currentUserCourse;

        if (!$userCourse) {
            abort(403, 'Вы не записаны на этот курс');
        }

        if ($userCourse->isStarted()) {
            abort(400, 'Курс уже начат');
        }

        $userCourse->start();
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
        if (!$userCourse->isFullyPassed()) {
            abort(400, 'Курс еще не пройден полностью.');
        }

        $user = $user ?? $userCourse->user;

        DB::transaction(function () use ($userCourse) {
            $userCourse->finish();
            GenerateCourseCertificateJob::dispatch($userCourse)->afterCommit();
        });


    }
}
