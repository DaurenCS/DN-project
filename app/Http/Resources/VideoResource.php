<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'url'        => $this->url,
            'duration'   => $this->duration,
            'sort_order' => $this->sort_order,
            'lesson_id'  => $this->lesson_id,
        ];
    }
}
