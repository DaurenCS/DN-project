<?php

namespace App\Services;

use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\TestAttempt;
use App\Models\UserCourse;
use App\Models\UserCourseLesson;
use Illuminate\Support\Facades\Gate;

class LessonService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getLesson($slug)
    {
        $lesson = Lesson::query()
            ->with(['module.course','videos', 'conspects', 'tests'])
            ->where('slug', $slug)
            ->firstOrFail();

        Gate::authorize('access', $lesson);

        $previous = Lesson::query()
            ->where('module_id', $lesson->module_id)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderByDesc('sort_order')
            ->value('slug');

        $next = Lesson::query()
            ->where('module_id', $lesson->module_id)
            ->where('sort_order', '>', $lesson->sort_order)
            ->orderBy('sort_order')
            ->value('slug');

        $lesson->setAttribute('previous', $previous);
        $lesson->setAttribute('next', $next);


        return LessonResource::make($lesson);


    }

    public function finishLesson($slug)
    {
        $lesson = Lesson::query()
            ->with(['module.course', 'tests'])
            ->where('slug', $slug)
            ->firstOrFail();

        Gate::authorize('access', $lesson);

        $userId = auth()->id();

        // ПРОВЕРКА: Сданы ли тесты урока (связь через user_id и lesson_id)
        $testIds = $lesson->tests->pluck('id')->toArray();
        if (!empty($testIds)) {
            $passedAttemptsCount = TestAttempt::query()
                ->where('user_id', $userId)
                ->where('lesson_id', $lesson->id)
                ->whereIn('test_id', $testIds)
                ->where('status', 'passed')
                ->distinct('test_id')
                ->count('test_id');

            if ($passedAttemptsCount < count($testIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Необходимо успешно сдать все тесты этого урока.'
                ], 403);
            }
        }

        // Если тестов нет или они сданы — закрываем урок
        $progress = $this->markAsCompleted($lesson, $userId);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Lesson marked as completed',
            'progress' => $progress . '%'
        ]);
    }

    public function markAsCompleted(Lesson $lesson, int $userId): int
    {
        $user = auth()->user() ?? \App\Models\User::find($userId);
        $course = $lesson->module->course;
        $userCourse = $user->getUserCourse($course->id);

        // Фиксируем прохождение урока
        UserCourseLesson::query()->firstOrCreate([
            'user_course_id' => $userCourse->id,
            'lesson_id'      => $lesson->id,
        ]);

        // Считаем прогресс курса
        $totalLessonsCount = Lesson::query()
            ->whereHas('module', fn($q) => $q->where('course_id', $course->id))
            ->count();

        $completedLessonsCount = UserCourseLesson::query()
            ->where('user_course_id', $userCourse->id)
            ->count();

        $progress = $totalLessonsCount > 0
            ? round(($completedLessonsCount / $totalLessonsCount) * 100)
            : 0;

        $userCourse->progress = $progress;
        $userCourse->save();

        return $progress;
    }
}
