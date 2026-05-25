<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCourseLesson extends Model
{
    protected $table = 'user_course_lesson';


    public function userCourse()
    {
        return $this->belongsTo(UserCourse::class);
    }

}
