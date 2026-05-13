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

    public function getCourses($slug = null)
    {
        $courses = Course::where('is_active', true)
            ->when($slug, function ($query, $slug) {
                return $query->where('slug', $slug);
            })
            ->get();

        return CourseResource::collection($courses);
    }
}
