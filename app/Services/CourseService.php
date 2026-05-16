<?php

namespace App\Services;

use App\Http\Resources\CourseResource;
use App\Models\Course;
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
            ->with(['modules', 'lessons'])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return CourseResource::make($course);

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
}
