<?php

namespace App\Services;

use App\Http\Resources\LessonResource;
use App\Models\Lesson;
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
            ->with(['module.course'])
            ->where('slug', $slug)
            ->firstOrFail();

        Gate::authorize('access', $lesson);

        $user = auth()->user();
        $course = $lesson->module->course;

        $userCourse = $user->getUserCourse($course->id);

        UserCourseLesson::query()->firstOrCreate([
            'user_course_id' => $userCourse->id,
            'lesson_id'   => $lesson->id,
        ]);

        $totalLessonsCount = Lesson::query()
            ->whereHas('module', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->count();

        $completedLessonsCount = UserCourseLesson::query()
            ->where('user_course_id', $userCourse->id)
            ->count();

        $progress = $totalLessonsCount > 0
            ? round(($completedLessonsCount / $totalLessonsCount) * 100)
            : 0;

        $userCourse->progress = $progress;
        $userCourse->save();

        return response()->json([
            'status'   => 'success',
            'message'  => 'Lesson marked as completed',
            'progress' => $progress . '%'
        ]);
    }
}
