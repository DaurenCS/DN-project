<?php

namespace App\Http\Resources;

use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TestResource extends JsonResource
{
    public function __construct(
        private Test $test,
        private TestAttempt $attempt,
        private Collection $questions,
        private array $savedAnswers,
    ) {
        parent::__construct($attempt);
    }

    public function toArray(Request $request): array
    {
        return [
            'attempt_id' => $this->attempt->id,
            'test_id'    => $this->test->id,
            'title'      => $this->test->title,
            'duration'   => $this->test->duration,
            'time_left'  => $this->resolveTimeLeft(),
            'questions'  => QuestionResource::collection(
                $this->questions->map(function ($question) {
                    $question->setAttribute(
                        'user_answer',
                        $this->savedAnswers[$question->id] ?? []
                    );

                    return $question;
                })
            ),
        ];
    }

    private function resolveTimeLeft(): ?int
    {
        if ($this->test->duration <= 0) {
            return null;
        }

        $expiresAt = $this->attempt->created_at->addMinutes($this->test->duration);

        return max(0, $expiresAt->getTimestamp() - now()->getTimestamp());
    }
}
