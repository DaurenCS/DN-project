<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userProgress = $this->extractUserProgress();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'image' => $this->image ? Storage::url($this->image) : null,
            'is_active' => (bool)$this->is_active,

            'user_progress' => $userProgress,
            'user_status' => $this->getUserStatus($userProgress),

            'modules_count' => $this->whenCounted('modules'),
            'lessons_count' => $this->whenCounted('lessons'),

            'created_at' => $this->created_at->toISOString(),
        ];
    }

    protected function extractUserProgress()
    {
        if ($this->pivot) {
            return [
                'start_date'              => $this->pivot->start_date,
                'progress' => (int) $this->pivot->progress,
                'completed_at'        => $this->pivot->end_date,
            ];
        }

        if ($this->relationLoaded('users') && $this->users->isNotEmpty()) {
            $pivot = $this->users->first()->pivot;

            return [
                'start_date'              => $pivot->start_date,
                'progress' => (int) $pivot->progress,
                'completed_at'        => $pivot->end_date,
            ];
        }

        return null;
    }

    protected function getUserStatus($userProgress)
    {
        if (is_null($userProgress)) {
            return 'buy';
        }

        if (!empty($userProgress['completed_at'])) {
            return 'completed';
        }

        if (!empty($userProgress['start_date'])) {
            return 'continue';
        }
        return 'start';

    }
}
