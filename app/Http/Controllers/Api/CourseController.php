<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CourseService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function getCourseList(Request $request)
    {
        return $this->courseService->getCourses($request);
    }

    public function getCourse($slug)
    {
        return $this->courseService->getCourse($slug);
    }

    public function getUserCourses()
    {
        return $this->courseService->getUserCourses();
    }
}
