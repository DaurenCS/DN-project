<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,

            'lesson_videos' => $this->videos,
            'lesson_conspects' => $this->conspects->map(function ($conspect) {
                return [
                    'id' => $conspect->id,
                    'title' => $conspect->title,
                    'content' => $conspect->content,
                ];
            }),
            'lesson_tests' => $this->tests,

            'previous' => $this->previous,
            'next' => $this->next,

        ];
    }
}
