<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userProgress = $this->getAuthUserProgress();

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'slug'          => $this->slug,
            'image'         => $this->image ? Storage::url($this->image) : null,
            'is_active'     => (bool) $this->is_active,

            'user_progress' => $userProgress,
            'user_status'   => $userProgress['status'] ?? 'buy',

            'modules_count' => $this->whenCounted('modules'),
            'lessons_count' => $this->whenCounted('lessons'),

            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
