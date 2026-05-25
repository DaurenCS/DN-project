<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseDetailResource extends CourseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['current_lesson_slug'] = $this->getCurrentLesson()?->slug;

        $data['modules'] = ModuleResource::collection($this->whenLoaded('modules'));

        return $data;
    }
}
