<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'content'     => $this->content,
            'course'      => [
                'id'   => $this->module->course->id,
                'name' => $this->module->course->name,
                'slug' => $this->module->course->slug,
            ],
            'lesson_videos'    => VideoResource::collection($this->whenLoaded('videos')),
            'lesson_conspects' => ConspectResource::collection($this->whenLoaded('conspects')),
            'lesson_tests'     => TestSummaryResource::collection($this->whenLoaded('tests')),
            'previous' => $this->previous,
            'next'     => $this->next,
        ];
    }
}
