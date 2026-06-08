<?php

namespace App\Services;

use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\UserCourse;
use App\Models\UserCourseLesson;

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
            ->with(['videos', 'conspects', 'tests'])
            ->where('slug', $slug)
            ->firstOrFail();

        $neighbors = Lesson::query()
            ->where('module_id', $lesson->module_id)
            ->where(function ($query) use ($lesson) {
                $query->where('sort_order', '<', $lesson->sort_order)
                    ->orderByDesc('sort_order')
                    ->limit(1);
            })
            ->orWhere(function ($query) use ($lesson) {
                $query->where('module_id', $lesson->module_id)
                    ->where('sort_order', '>', $lesson->sort_order)
                    ->orderBy('sort_order')
                    ->limit(1);
            })
            ->get();

        $previous = $neighbors->first(fn($item) => $item->sort_order < $lesson->sort_order)?->slug;
        $next = $neighbors->first(fn($item) => $item->sort_order > $lesson->sort_order)?->slug;


        $lesson->setAttribute('previous', $previous);
        $lesson->setAttribute('next', $next);


        return LessonResource::make($lesson);


    }

    public function finishLesson($slug)
    {
        $auth = auth()->user();

        $lesson = Lesson::query()
            ->with(['module.course'])
            ->where('slug', $slug)
            ->firstOrFail();

        $course = $lesson->module->course;

//        dd($course->);
        $user_course = UserCourse::query()
            ->where('course_id', $course->id)
            ->where('user_id', $auth->id)
            ->firstOrFail();

        UserCourseLesson::query()->create([
            'user_course' => $user_course->id,
            'lesson_id' => $lesson->id,
        ]);

    }
}
