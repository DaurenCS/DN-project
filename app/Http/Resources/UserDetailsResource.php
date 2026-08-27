<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $stats = $this->resource['stats'];
        $lastCourse = $this->resource['last_course'];
        $nextLesson = $this->resource['next_lesson'];

        $continueLearning = null;

        if ($lastCourse && $nextLesson) {
            $continueLearning = [
                'course_id'    => $lastCourse->id,
                'course_title' => $lastCourse->name,
                'course_slug'  => $lastCourse->slug ?? null,
                'next_lesson'  => [
                    'id'         => $nextLesson->id,
                    'title'      => $nextLesson->name,
                    'slug'       => $nextLesson->slug,
                    'sort_order' => $nextLesson->sort_order,
                ],
            ];
        }

        return [
            'user' => [
                'id'          => $user->id,
                'first_name'  => $user->name,
                'second_name' => $user->second_name,
                'full_name'   => trim("{$user->name} {$user->second_name}"),
                'phone'       => $user->phone,
                'position'    => $user->position,
                'department'  => $user->department ? [
                    'id'   => $user->department->id,
                    'name' => $user->department->name,
                ] : null,
                'created_at'  => $user->created_at?->toISOString(),
            ],
            'stats' => $stats,
            'continue_learning' => $continueLearning,
        ];
    }
}
