<?php

namespace App\Services;

use App\Http\Resources\CourseResource;
use App\Models\Course;

class CourseService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getCourses()
    {
        $courses = Course::where('is_active', true)->get();

        return CourseResource::collection($courses);
    }
}
