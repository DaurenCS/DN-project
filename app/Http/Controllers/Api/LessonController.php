<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LessonService;

class LessonController extends Controller
{
    public function __construct(LessonService $lessonService)
    {
        $this->lessonService = $lessonService;
    }

    public function getLesson($slug)
    {
        return $this->lessonService->getLesson($slug);
    }

    public function finishLesson($slug)
    {
        return $this->lessonService->finishLesson($slug);
    }
}
