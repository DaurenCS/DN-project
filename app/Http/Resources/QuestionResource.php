<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'text'          => $this->question_text,
            'point'         => $this->point,
            'question_type' => $this->type?->id,
            'answered'      => collect($this->user_answer)->isNotEmpty(),
            'user_answer'   => AnswerResource::collection($this->user_answer ?? collect()),
            'answers'       => AnswerResource::collection($this->answers),
        ];
    }
}
