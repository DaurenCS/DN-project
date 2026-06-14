<?php

namespace App\Services;

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
            ->where('test_id', $test->id)
            ->where('status', 'in_progress');

        $this->applyLessonFilter($query, $lessonId ?? null);

        $activeAttempt = $query->first();

        if ($activeAttempt) {
            if ($test->duration > 0 && now()->greaterThan($activeAttempt->created_at->addMinutes($test->duration))) {
                $activeAttempt->update(['status' => 'failed']);
            } else {
                return $activeAttempt;
            }
        }

        $shuffledQuestionIds = Question::query()
            ->where('test_id', $test->id)
            ->inRandomOrder()
            ->pluck('id')
            ->toArray();

        return TestAttempt::query()->create([
            'user_id'         => $userId,
            'lesson_id'       => $lesson?->id,
            'test_id'         => $test->id,
            'total_questions' => count($shuffledQuestionIds),
            'question_ids'    => $shuffledQuestionIds,
            'correct_answers' => 0,
            'percent'         => 0,
            'status'          => 'in_progress',
        ]);
    }

    public function getTest(Test $test, ?int $lessonId = null, int $userId): array
    {

        $attempt = $this->startTest($test, $lessonId, $userId);
        $orderedIds = $attempt->question_ids;

        if (empty($orderedIds)) {
            return $this->formatTestResponse($test, $attempt, collect(), []);
        }

        $questions = Question::query()
            ->whereIn('id', $orderedIds)
            ->with(['answers' => function ($query) {
                $query->select('id', 'question_id', 'answer'); // Безопасно: без is_correct
            }])
             ->orderByRaw("position(id::text in '" . implode(',', $orderedIds) . "')") // Для PostgreSQL
            ->get();

        $savedAnswers = DB::table('test_attempt_answers')
            ->where('test_attempt_id', $attempt->id)
            ->pluck('answer_id', 'question_id')
            ->toArray();

        return $this->formatTestResponse($test, $attempt, $questions, $savedAnswers);

    }
    public function saveQuestionAnswer(Test $test, int $userId, int $questionId, array $answerIds,  ?int $lessonId = null): void
    {
        DB::transaction(function () use ($test, $userId, $questionId, $answerIds, $lessonId) {

            $query = TestAttempt::query()
                ->where('user_id', $userId)
                ->where('test_id', $test->id)
                ->where('status', 'in_progress');

            $this->applyLessonFilter($query, $lessonId);

            $attempt = $query->firstOrFail();

            if ($test->duration > 0 && now()->greaterThan($attempt->created_at->addMinutes($test->duration))) {
                $attempt->update(['status' => 'failed']);
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
    public function submitTest(Test $test, int $userId, ?int $lessonId = null): TestAttempt
    {
        return DB::transaction(function () use ($test, $userId, $lessonId) {
            $lesson = $lessonId ? Lesson::query()->findOrFail($lessonId) : null;

            $query = TestAttempt::query()
                ->where('user_id', $userId)
                ->where('test_id', $test->id)
                ->where('status', 'in_progress');

            $this->applyLessonFilter($query, $lessonId);

            $attempt = $query->firstOrFail();

            if ($test->duration > 0 && now()->greaterThan($attempt->created_at->addMinutes($test->duration))) {
                $attempt->update(['status' => 'failed']);
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
            $status = $percent >= $test->passing_score ? 'passed' : 'failed';

            $attempt->update([
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'percent'         => $percent,
                'status'          => $status,
            ]);

            if ($status === 'passed' && $lesson) {
                $this->autoFinishLessonIfAllTestsPassed($lesson, $userId);
            }

            return $attempt;
        });
    }

    private function formatTestResponse(Test $test, TestAttempt $attempt, $questions, array $savedAnswers): array
    {
        return [
            'attempt_id'    => $attempt->id,
            'test_id'       => $test->id,
            'title'         => $test->title,
            'duration'      => $test->duration,
            'time_left'     => $test->duration > 0
                ? max(0, $attempt->created_at->addMinutes($test->duration)->diffInSeconds(now(), false) * -1)
                : null,
            'questions'     => $questions->map(fn($question) => [
                'id'            => $question->id,
                'text'          => $question->question_text,
                'point'         => $question->point,
                'question_type' => $question->type?->id,
                'user_answer'   => $savedAnswers[$question->id] ?? [],
                'answers'       => $question->answers->map(fn($answer) => [
                    'id'   => $answer->id,
                    'text' => $answer->answer,
                ])
            ]),
        ];
    }

    private function autoFinishLessonIfAllTestsPassed(Lesson $lesson, int $userId): void
    {
        $testIds = $lesson->tests->pluck('id')->toArray();

        $passedAttemptsCount = TestAttempt::query()
            ->where('user_id', $userId)
            ->where('lesson_id', $lesson->id)
            ->whereIn('test_id', $testIds)
            ->where('status', 'passed')
            ->distinct('test_id')
            ->count('test_id');

        if ($passedAttemptsCount >= count($testIds)) {
            app(LessonService::class)->markAsCompleted($lesson, $userId);
        }
    }

    private function applyLessonFilter($query, ?int $lessonId): void
    {
        if ($lessonId !== null) {
            $query->where('lesson_id', $lessonId);
        }
    }
}
