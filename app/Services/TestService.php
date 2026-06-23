<?php

namespace App\Services;

use App\Http\Resources\TestResource;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestAttemptAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TestService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function startTest(Test $test, ?int $lessonId, int $userId): TestAttempt
    {
        $lesson = $lessonId ? Lesson::query()->findOrFail($lessonId) : null;

        if ($lesson) {
            Gate::authorize('access', $lesson);
        }

        $query = TestAttempt::query()
            ->where('user_id', $userId)
            ->where('test_id', $test->id);

        $this->applyLessonFilter($query, $lessonId ?? null);

        $existingAttempt = $query->first();

        $shuffledQuestionIds = Question::query()
            ->where('test_id', $test->id)
            ->inRandomOrder()
            ->pluck('id')
            ->toArray();


        if ($existingAttempt) {
            if (
                $existingAttempt->status === 'in_progress' &&
                !($test->duration > 0 && now()->greaterThan($existingAttempt->created_at->addMinutes($test->duration)))
            ) {
                return $existingAttempt;
            }

            TestAttemptAnswer::where('test_attempt_id', $existingAttempt->id)->delete();

            $existingAttempt->update([
                'status'          => 'in_progress',
                'attempts'        => $existingAttempt->attempts + 1,
                'question_ids'    => $shuffledQuestionIds,
                'total_questions' => count($shuffledQuestionIds),
                'correct_answers' => 0,
                'percent'         => 0,
                'started_at'      => now(),
            ]);

            return $existingAttempt->fresh();
        }
        return TestAttempt::query()->create([
            'user_id'         => $userId,
            'lesson_id'       => $lesson?->id,
            'test_id'         => $test->id,
            'total_questions' => count($shuffledQuestionIds),
            'question_ids'    => $shuffledQuestionIds,
            'correct_answers' => 0,
            'percent'         => 0,
            'attempts'        => 1,
            'started_at' => now(),
            'status'          => TestAttempt::STATUS_IN_PROGRESS,
        ]);
    }

    public function getTest(Test $test, int $userId, ?int $lessonId = null): TestResource
    {
        $attempt = $this->startTest($test, $lessonId, $userId);
        $orderedIds = $attempt->question_ids;

        if (empty($orderedIds)) {
            return new TestResource($test, $attempt, collect(), []);
        }

        $questions = Question::query()
            ->whereIn('id', $orderedIds)
            ->with(['answers' => function ($query) {
                $query->select('id', 'question_id', 'answer');
            }])
            ->get()
            ->sortBy(fn ($question) => array_search($question->id, $orderedIds))
            ->values();

        $savedAnswers = DB::table('test_attempt_answers')
            ->where('test_attempt_id', $attempt->id)
            ->get()
            ->groupBy('question_id')
            ->map(function ($items) {
                return $items->pluck('answer_id')->toArray();
            })
            ->toArray();

        return new TestResource($test, $attempt, $questions, $savedAnswers);
    }
    public function saveQuestionAnswer(Test $test, int $userId, int $questionId, array $answerIds,  ?int $lessonId = null): void
    {
        DB::transaction(function () use ($test, $userId, $questionId, $answerIds, $lessonId) {

            $query = TestAttempt::query()
                ->where('user_id', $userId)
                ->where('test_id', $test->id)
                ->where('status', TestAttempt::STATUS_IN_PROGRESS);

            $this->applyLessonFilter($query, $lessonId);

            $attempt = $query->firstOrFail();

            $startTime = $attempt->started_at ?? $attempt->created_at;
            if ($test->duration > 0 && now()->greaterThan($startTime->addMinutes($test->duration))) {
                $attempt->update(['status' => TestAttempt::STATUS_FAILED]);
                abort(403, 'Время на прохождение теста истекло.');
            }

            TestAttemptAnswer::query()
                ->where('test_attempt_id', $attempt->id)
                ->where('question_id', $questionId)
                ->delete();

            $records = array_map(fn($answerId) => [
                'test_attempt_id' => $attempt->id,
                'question_id'     => $questionId,
                'answer_id'       => $answerId,
            ], $answerIds);

            if (!empty($records)) {
                TestAttemptAnswer::query()->insert($records);
            }
        });
    }
    public function submitTest(Test $test, ?int $lessonId = null): TestAttempt
    {
        $userId = auth()->id();

        return DB::transaction(function () use ($test, $userId, $lessonId) {
            $lesson = $lessonId ? Lesson::query()->findOrFail($lessonId) : null;

            $query = TestAttempt::query()
                ->where('user_id', $userId)
                ->where('test_id', $test->id)
                ->where('status', TestAttempt::STATUS_IN_PROGRESS);

            $this->applyLessonFilter($query, $lessonId);

            $attempt = $query->firstOrFail();

            $startTime = $attempt->started_at ?? $attempt->created_at;
            if ($test->duration > 0 && now()->greaterThan($startTime->addMinutes($test->duration))) {
                $attempt->update(['status' => TestAttempt::STATUS_FAILED]);
                return $attempt;
            }

            $correctAnswersMap = DB::table('answers')
                ->whereIn('question_id', $test->questions()->pluck('id'))
                ->where('is_correct', true)
                ->get()
                ->groupBy('question_id')
                ->map(fn($items) => $items->pluck('id')->toArray())
                ->toArray();

            $userAnswersMap = TestAttemptAnswer::query()
                ->where('test_attempt_id', $attempt->id)
                ->get()
                ->groupBy('question_id')
                ->map(fn($items) => $items->pluck('answer_id')->toArray())
                ->toArray();

            $totalQuestions = count($correctAnswersMap);
            $correctCount = 0;

            foreach ($correctAnswersMap as $questionId => $correctIds) {
                $submittedIds = $userAnswersMap[$questionId] ?? [];

                sort($correctIds);
                sort($submittedIds);

                if ($correctIds === $submittedIds) {
                    $correctCount++;
                }
            }

            $percent = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;
            $status = $percent >= $test->passing_score ? TestAttempt::STATUS_PASSED : TestAttempt::STATUS_FAILED;

            $attempt->update([
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'percent'         => $percent,
                'status'          => $status,
            ]);

            if ($status === TestAttempt::STATUS_PASSED && $lesson) {
                $this->autoFinishLessonIfAllTestsPassed($lesson, $userId);
            }

            return $attempt;
        });
    }

    public function results(Test $test, ?int $lessonId = null)
    {
        $testAttempt = TestAttempt::query()
            ->where('user_id', auth()->id())
            ->where('test_id', $test->id)
            ->where('lesson_id', $lessonId)
            ->whereNot('status', TestAttempt::STATUS_IN_PROGRESS)
            ->firstOrFail();

        $questionIds = $testAttempt->question_ids;

        $questions = $test->questions()
            ->with(['answers:id,question_id,answer,is_correct'])
            ->get()
            ->sortBy(fn($q) => array_search($q->id, $questionIds))
            ->values();

        $userAnswers = TestAttemptAnswer::query()
            ->where('test_attempt_id', $testAttempt->id)
            ->get()
            ->groupBy('question_id')
            ->map(fn($items) => $items->pluck('answer_id')->toArray());

        $results = $questions->map(function ($question) use ($userAnswers) {
            $submittedIds = $userAnswers[$question->id] ?? [];
            $correctIds = $question->answers->where('is_correct', true)->pluck('id')->toArray();

            sort($submittedIds);
            sort($correctIds);

            return [
                'question_id' => $question->id,
                'text'        => $question->question_text,
                'submitted'   => $submittedIds,
                'is_correct'  => $submittedIds === $correctIds,
                'answers'     => $question->answers->map(fn($a) => [
                    'id' => $a->id,
                    'text' => $a->answer,
                    'is_correct' => (bool)$a->is_correct
                ])
            ];
        });

        return [
            'attempt' => $testAttempt,
            'results' => $results
        ];

    }


    private function autoFinishLessonIfAllTestsPassed(Lesson $lesson, int $userId): void
    {
        $lessonService = app(LessonService::class);

        if ($lessonService->allTestsPassed($lesson, $userId)) {
            $lessonService->markAsCompleted($lesson, $userId);
        }
    }

    private function applyLessonFilter($query, ?int $lessonId): void
    {
        if ($lessonId !== null) {
            $query->where('lesson_id', $lessonId);
        }
    }
}
