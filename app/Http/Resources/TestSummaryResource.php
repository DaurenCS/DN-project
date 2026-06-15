<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'duration'      => $this->duration,
            'passing_score' => $this->passing_score,
            'sort_order'    => $this->sort_order,
            'passed'        => (bool) ($this->passed ?? false),
        ];
    }
}
