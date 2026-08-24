<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Статистика курсов — один запрос вместо трёх
        $courseStats = $this->userCourses()
            ->selectRaw("
                SUM(CASE WHEN status != 'completed' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
            ")
            ->first();

        $activeCoursesCount = (int) ($courseStats->active_count ?? 0);
        $completedCoursesCount = (int) ($courseStats->completed_count ?? 0);

        $certificatesCount = $this->certificates()->count();

        $continueLearning = null;

        $lastUserCourse = $this->userCourses()
            ->where('status', '!=', 'completed')
            ->with(['course.modules.lessons'])
            ->latest('updated_at')
            ->first();

        if ($lastUserCourse && $lastUserCourse->course) {
            $nextLesson = $lastUserCourse->course->getCurrentLesson();

            if ($nextLesson) {
                $continueLearning = [
                    'course_id'    => $lastUserCourse->course->id,
                    'course_title' => $lastUserCourse->course->name,
                    'course_slug'  => $lastUserCourse->course->slug ?? null,
                    'next_lesson'  => [
                        'id'         => $nextLesson->id,
                        'title'      => $nextLesson->name,
                        'slug'       => $nextLesson->slug,
                        'sort_order' => $nextLesson->sort_order,
                    ],
                ];
            }
        }

        return [
            'user' => [
                'id'          => $this->id,
                'first_name'  => $this->name,
                'second_name' => $this->second_name,
                'full_name'   => trim("{$this->name} {$this->second_name}"),
                'phone'       => $this->phone,
                'position'    => $this->position,
                'department'  => $this->department ? [
                    'id'   => $this->department->id,
                    'name' => $this->department->name,
                ] : null,
                'created_at'  => $this->created_at?->toISOString(),
            ],
            'stats' => [
                'active_courses_count'    => $activeCoursesCount,
                'completed_courses_count' => $completedCoursesCount,
                'certificates_count'      => $certificatesCount,
            ],
            'continue_learning' => $continueLearning,
        ];
    }
}
