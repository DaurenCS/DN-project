<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\Question;
use App\Services\TestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TestController extends Controller
{
    protected TestService $testService;
    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    public function show(Request $request, Test $test): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => 'nullable|integer|exists:lessons,id',
        ]);
        $userId = auth()->user()->id;

        $data = $this->testService->getTest($test, $userId,$validated['lesson_id'] ?? null, );


        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    public function saveAnswer(Request $request, Test $test, Question $question): JsonResponse
    {
        $validated = $request->validate([
            'answer_ids'   => 'present|array',
            'answer_ids.*' => 'integer|exists:answers,id',
            'lesson_id'    => 'nullable|integer|exists:lessons,id',
        ]);

        $this->testService->saveQuestionAnswer(
            $test,
            auth()->id(),
            $question->id,
            $validated['answer_ids'],
            $validated['lesson_id'] ?? null
        );

        return response()->json(['status' => 'success', 'message' => 'Answer saved successfully']);
    }


    public function submit(Request $request, Test $test): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => 'nullable|integer|exists:lessons,id',
        ]);

        $attempt = $this->testService->submitTest($test, auth()->id(), $validated['lesson_id'] ?? null);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'attempt_id'      => $attempt->id,
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'percent'         => $attempt->percent,
                'status'          => $attempt->status,
            ],
        ]);
    }

    public function getResults(Test $test): JsonResponse
    {
        $this->testService->results($test);
    }
}
