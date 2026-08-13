<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCoursesRequest;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function __construct(private CourseService $courseService) {}

    public function getCourseList(GetCoursesRequest $request)
    {
        $courses = $this->courseService->getCourses($request->validated('per_page', 10));

        return CourseResource::collection($courses);
    }

    public function getCourse(Request $request, string $slug): CourseDetailResource
    {
        $course = $this->courseService->getCourse($slug, $request->user());

        return CourseDetailResource::make($course);
    }

    public function getUserCourses(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->getUserCourses($request->user());

        return CourseResource::collection($courses);
    }

    public function start(Request $request, string $slug): JsonResponse
    {
        $this->courseService->start($request->user(), $slug);

        return response()->json(['message' => 'Курс успешно начат']);
    }

    public function finish(Request $request, string $slug): JsonResponse
    {
        $this->courseService->finish($request->user(), $slug);

        return response()->json(['message' => 'Курс успешно завершен']);
    }

    public function buy(string $slug): JsonResponse
    {
        return response()->json(['message' => 'В разработке'], 501);
    }
}
