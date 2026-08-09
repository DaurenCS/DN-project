<?php

namespace App\Services;

use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\UserCourseLesson;
use Illuminate\Support\Facades\Gate;

class LessonService
{
    public function __construct(protected CourseService $courseService) {}
    public function getLesson(string $slug): LessonResource
    {
        $userId = auth()->id();

        $lesson = Lesson::query()
            ->with(['module.course', 'videos', 'conspects', 'tests' => function ($query) use ($userId) {
                $query->with(['attempts' => function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('status', TestAttempt::STATUS_PASSED);
                }]);
            }])
            ->where('slug', $slug)
            ->firstOr(function () {
                abort(404, 'Урок не найден');
            });

        Gate::authorize('access', $lesson);

        $courseId = $lesson->module->course_id;
        $currentModuleOrder = $lesson->module->order;
        $currentLessonOrder = $lesson->sort_order;

        $previous = Lesson::query()
            ->select('lessons.slug')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $courseId)
            ->where(function ($query) use ($currentModuleOrder, $currentLessonOrder) {
                $query->where('modules.order', '<', $currentModuleOrder)
                    ->orWhere(function ($q) use ($currentModuleOrder, $currentLessonOrder) {
                        $q->where('modules.order', '=', $currentModuleOrder)
                            ->where('lessons.sort_order', '<', $currentLessonOrder);
                    });
            })
            ->orderBy('modules.order', 'desc')
            ->orderBy('lessons.sort_order', 'desc')
            ->value('lessons.slug');

        $next = Lesson::query()
            ->select('lessons.slug')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $courseId)
            ->where(function ($query) use ($currentModuleOrder, $currentLessonOrder) {
                $query->where('modules.order', '>', $currentModuleOrder)
                    ->orWhere(function ($q) use ($currentModuleOrder, $currentLessonOrder) {
                        $q->where('modules.order', '=', $currentModuleOrder)
                            ->where('lessons.sort_order', '>', $currentLessonOrder);
                    });
            })
            ->orderBy('modules.order', 'asc')
            ->orderBy('lessons.sort_order', 'asc')
            ->value('lessons.slug');

        $lesson->setAttribute('previous', $previous);
        $lesson->setAttribute('next', $next);

        $lesson->tests->each(function ($test) {
            $test->setAttribute(TestAttempt::STATUS_PASSED, $test->attempts->isNotEmpty());
        });

        return LessonResource::make($lesson);
    }

    public function finishLesson(string $slug): int
    {
        $lesson = Lesson::query()
            ->with(['module.course', 'tests'])
            ->where('slug', $slug)
            ->firstOrFail();

        Gate::authorize('access', $lesson);

        $userId = auth()->id();

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

    public function markAsCompleted(Lesson $lesson, int $userId): int
    {
        $user = auth()->user() ?? User::findOrFail($userId);
        $course = $lesson->module->course;
        $userCourse = $user->getUserCourse($course->id);

        if (!$userCourse) {
            abort(403, 'Пользователь не записан на этот курс.');
        }

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
            ? round(($completedLessonsCount / $totalLessonsCount) * 100)
            : 0;

        $userCourse->progress = $progress;
        $userCourse->save();

        if ($progress >= 100) {
            try {
                $this->courseService->completeCourse($userCourse);
            } catch (\Exception $e) {

            }
        }

        return $progress;
    }
}
