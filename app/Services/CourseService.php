<?php

namespace App\Services;

use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\UserCourse;
use App\Models\UserCourseLesson;
use Illuminate\Http\Request;

class CourseService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getCourses(Request $request)
    {
        $perPage = $request->query('perPage', 10);

        $courses = Course::query()
            ->withCount(['modules', 'lessons'])
            ->where('is_active', true)
            ->withAuthUserProgress()
            ->orderBy('id', 'desc')
            ->paginate($perPage);


        return CourseResource::collection($courses);
    }

    public function getCourse($slug)
    {
        $course = Course::query()
            ->withAuthUserProgress()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['modules', 'lessons'])
            ->with(['modules.lessons' => function ($query) {
                $query->withExists('currentAuthProgress');
            }])
            ->firstOrFail();

        return CourseDetailResource::make($course);

    }
    public function getUserCourses()
    {
        $user = auth()->user();
        $courses = $user->courses()
            ->where('is_active', true)
            ->withCount(['modules', 'lessons'])
            ->get();

        return CourseResource::collection($courses);

    }

    public function start($slug)
    {
        $user = auth()->user();
        $course = Course::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $userCourse = UserCourse::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($userCourse->start_date) {
            return response()->json(['message' => 'Course already started'], 400);
        }

        $userCourse->update([
            'start_date' => now(),
        ]);

        return response()->json(['message' => 'Course started'], 200);

    }
}
