<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'course_type_id',
        'image',
        'is_active',
        'slug',
        'is_sequential'
    ];

    public $translatable = [
        'name',
        'description',
    ];

    public function scopeWithAuthUserProgress(Builder $query): Builder
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return $query;
        }
        return $query->with(['users' => function ($q) use ($user) {
            $q->where('users.id', $user->id);
        }]);
    }

    public function courseType()
    {
        return $this->belongsTo(CourseType::class, 'course_type_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function lessons() {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_course')
            ->using(UserCourse::class)
            ->withPivot(['id','start_date', 'end_date', 'progress'])
            ->withTimestamps();
    }

    public function getCurrentLesson()
    {
        if (!$this->relationLoaded('modules')) {
            return null;
        }
        return $this->modules
            ->flatMap(fn($module) => $module->lessons)
            ->first(fn($lesson) => !$lesson->current_auth_progress_exists);

    }


}
