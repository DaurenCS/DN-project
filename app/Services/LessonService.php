<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\UserCourseLesson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class LessonService
{
    public function getLesson(string $slug): Lesson
    {
        $userId = auth()->id();

        $lesson = Lesson::query()
            ->with([
                'module.course',
                'videos',
                'conspects',
                'tests' => function ($query) use ($userId) {
                    $query->with(['attempts' => function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                            ->latest();
                    }]);
                },
            ])
            ->where('slug', $slug)
            ->firstOr(function () {
                abort(404, 'Урок не найден');
            });

        Gate::authorize('access', $lesson);

        $lesson->setAttribute('previous', $this->findAdjacentLessonSlug($lesson, 'previous'));
        $lesson->setAttribute('next', $this->findAdjacentLessonSlug($lesson, 'next'));

        $lesson->tests->each(function ($test) {
            $attempts = $test->attempts;

            if ($attempts->isEmpty()) {
                $status = 'start';
            } elseif ($attempts->contains('status', TestAttempt::STATUS_PASSED)) {
                $status = 'passed';
            } else {
                $latestAttempt = $attempts->first();

                $status = match ($latestAttempt->status) {
                    TestAttempt::STATUS_IN_PROGRESS => 'in_progress',
                    TestAttempt::STATUS_FAILED      => 'failed',
                    default                         => 'start',
                };
            }

            $test->setAttribute('status', $status);
        });

        return $lesson;
    }

    public function finishLesson(string $slug): string
    {
        $lesson = Lesson::query()
            ->with(['module.course', 'tests'])
            ->where('slug', $slug)
            ->firstOr(function () {
                abort(404, 'Урок не найден');
            });

        Gate::authorize('access', $lesson);

        $userId = auth()->id();

        $isCompleted = $lesson->currentAuthProgress()->get()->isNotEmpty();

        if ($isCompleted) {
            return 'Урок уже сдан';
        }

        if (!$this->allTestsPassed($lesson, $userId)) {
            abort(403, 'Необходимо успешно сдать все тесты этого урока.');
        }

        return $this->markAsCompleted($lesson, $userId);
    }

    public function allTestsPassed(Lesson $lesson, int $userId): bool
    {
        $testIds = $lesson->tests->pluck('id')->toArray();

        if (empty($testIds)) {
            return true;
        }

        $passedCount = TestAttempt::query()
            ->where('user_id', $userId)
            ->where('lesson_id', $lesson->id)
            ->whereIn('test_id', $testIds)
            ->where('status', TestAttempt::STATUS_PASSED)
            ->distinct('test_id')
            ->count('test_id');

        return $passedCount >= count($testIds);
    }

    public function markAsCompleted(Lesson $lesson, int $userId): string
    {
        return DB::transaction(function () use ($lesson, $userId) {
            $user = auth()->user() ?? User::findOrFail($userId);
            $course = $lesson->module->course;

            $userCourse = $user->getUserCourse($course->id);

            if (!$userCourse) {
                abort(403, 'Пользователь не записан на этот курс.');
            }


            $userCourse = $userCourse->newQuery()->lockForUpdate()->find($userCourse->id);

            UserCourseLesson::query()->firstOrCreate([
                'user_course_id' => $userCourse->id,
                'lesson_id'      => $lesson->id,
            ]);

            $totalLessonsCount = Lesson::query()
                ->whereHas('module', fn ($q) => $q->where('course_id', $course->id))
                ->count();

            $completedLessonsCount = UserCourseLesson::query()
                ->where('user_course_id', $userCourse->id)
                ->count();

            $progress = $totalLessonsCount > 0
                ? (int) round(($completedLessonsCount / $totalLessonsCount) * 100)
                : 0;

            $userCourse->progress = $progress;
            $userCourse->save();
            Cache::forget("user_dashboard_" . auth()->id());

            if ($progress >= 100) {
                try {
                    app(CourseService::class)->completeCourse($userCourse);
                } catch (Throwable $e) {
                    Log::error('Failed to complete course after final lesson', [
                        'user_course_id' => $userCourse->id,
                        'course_id'      => $course->id,
                        'user_id'        => $userId,
                        'exception'      => $e->getMessage(),
                    ]);
                }
            }

            return 'Урок успешно сдан';
        });
    }

    private function findAdjacentLessonSlug(Lesson $lesson, string $direction): ?string
    {
        $courseId = $lesson->module->course_id;
        $moduleOrder = $lesson->module->order;
        $lessonOrder = $lesson->sort_order;

        $isNext = $direction === 'next';
        $comparator = $isNext ? '>' : '<';

        return Lesson::query()
            ->select('lessons.slug')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $courseId)
            ->where(function ($query) use ($comparator, $moduleOrder, $lessonOrder) {
                $query->where('modules.order', $comparator, $moduleOrder)
                    ->orWhere(function ($q) use ($comparator, $moduleOrder, $lessonOrder) {
                        $q->where('modules.order', '=', $moduleOrder)
                            ->where('lessons.sort_order', $comparator, $lessonOrder);
                    });
            })
            ->orderBy('modules.order', $isNext ? 'asc' : 'desc')
            ->orderBy('lessons.sort_order', $isNext ? 'asc' : 'desc')
            ->value('lessons.slug');
    }

    public function calculateAccessForCourse(Course $course, ?User $user, Collection $lessons): void
    {
        if (!$user || !$course->currentUserCourse) {
            foreach ($lessons as $lesson) {
                $lesson->can_pass = false;
            }
            return;
        }

        if (!$course->is_sequential) {
            foreach ($lessons as $lesson) {
                $lesson->can_pass = true;
            }
            return;
        }

        $canAccessNext = true;

        foreach ($lessons as $lesson) {
            $lesson->can_pass = $canAccessNext;

            $isCompleted = (bool) ($lesson->current_auth_progress_exists ?? false);

            $canAccessNext = $lesson->can_pass && $isCompleted;
        }
    }

}
