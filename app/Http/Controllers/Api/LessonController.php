<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
    public function __construct(protected readonly LessonService $lessonService) {}

    public function getLesson(string $slug): LessonResource
    {
        $lesson = $this->lessonService->getLesson($slug);

        return LessonResource::make($lesson);
    }

    public function finishLesson(string $slug): JsonResponse
    {
        $message = $this->lessonService->finishLesson($slug);

        return response()->json(['message' => $message]);
    }
}
