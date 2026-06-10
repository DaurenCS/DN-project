<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserCourseLesson;
use Illuminate\Auth\Access\Response;

class LessonPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function access(User $user, Lesson $lesson): Response
    {
        $course = $lesson->module?->course;

        if (!$course) {
            return Response::deny('Данный урок не принадлежит ни одному курсу.');
        }

        $userCourse = $user->getUserCourse($course->id);

        if (!$userCourse) {
            return Response::deny('У вас нет доступа к данному курсу.');
        }

        if (!$course->is_sequential) {
            return Response::allow();
        }

        $previousLessonId = Lesson::query()
            ->whereHas('module', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->where(function ($query) use ($lesson) {
                $query->whereHas('module', function ($q) use ($lesson) {
                    $q->where('order', '<', $lesson->module->order);
                })
                    ->orWhere(function ($q) use ($lesson) {
                        $q->where('module_id', $lesson->module_id)
                            ->where('sort_order', '<', $lesson->sort_order);
                    });
            })
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->orderByDesc('modules.order')
            ->orderByDesc('lessons.sort_order')
            ->select('lessons.id')
            ->value('lessons.id');

        if (!$previousLessonId) {
            return Response::allow();
        }

        $isPreviousCompleted = UserCourseLesson::query()
            ->where('user_course_id', $userCourse->id)
            ->where('lesson_id', $previousLessonId)
            ->exists();

        return $isPreviousCompleted
            ? Response::allow()
            : Response::deny('Сначала необходимо пройти предыдущий урок.');
    }


}
