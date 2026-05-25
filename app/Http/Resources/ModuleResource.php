<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $lessons = $this->whenLoaded('lessons')
            ->map(function ($lesson) {
                return [
                    'name' => $lesson->name,
                    'description' => $lesson->description,
                    'slug' => $lesson->slug,
                    'is_completed' => (bool)$lesson->current_auth_progress_exists,
                ];
            });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'lessons' => $lessons,
        ];
    }

}
