<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCoursesRequest;
use App\Services\CourseService;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseDetailResource;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(private CourseService $courseService) {}

    public function getCourseList(GetCoursesRequest $request)
    {
        $courses = $this->courseService->getCourses($request->validated('per_page', 10));

        return CourseResource::collection($courses);
    }

    public function getCourse($slug)
    {
        $course = $this->courseService->getCourse($slug);

        return CourseDetailResource::make($course);
    }

    public function getUserCourses(Request $request)
    {
        $courses = $this->courseService->getUserCourses($request->user());

        return CourseResource::collection($courses);
    }

    public function start(Request $request, $slug)
    {
        $this->courseService->start($request->user(), $slug);

        return response()->json(['message' => 'Курс успешно начат']);
    }

    public function finish(Request $request, $slug)
    {
        $this->courseService->finish($request->user(), $slug);

        return response()->json(['message' => 'Курс успешно завершен']);
    }

    public function buy($slug)
    {
        return response()->json(['message' => 'В разработке'], 501);
    }
}
