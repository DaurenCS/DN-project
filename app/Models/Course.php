<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
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

    protected ?Collection $cachedAllLessons = null;

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

    public function scopeWithCurrentLessonData(Builder $query): Builder
    {
        return $query->with([
            'modules' => fn ($q) => $q->orderBy('order'),
            'modules.lessons' => fn ($q) => $q
                ->withExists('currentAuthProgress')
                ->orderBy('sort_order'),
        ]);
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
            ->withPivot(['id','start_date', 'end_date', 'progress', 'status'])
            ->withTimestamps();
    }

    public function allLessons(): Collection
    {
        if ($this->cachedAllLessons !== null) {
            return $this->cachedAllLessons;
        }

        if (!$this->relationLoaded('modules')) {
            $this->load(['modules' => fn ($q) => $q->orderBy('order')]);
        }

        return $this->cachedAllLessons = $this->modules->flatMap(
            fn ($module) => $module->lessons
        );
    }

    public function getCurrentLesson()
    {
        if ($this->hasCurrentLessonProgressLoaded()) {
            return $this->allLessons()
                ->first(fn ($lesson) => !$lesson->current_auth_progress_exists);
        }

        return Lesson::query()
            ->select('lessons.id', 'lessons.name', 'lessons.slug', 'lessons.sort_order', 'lessons.module_id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $this->id)
            ->whereDoesntHave('currentAuthProgress')
            ->orderBy('modules.order', 'asc')
            ->orderBy('lessons.sort_order', 'asc')
            ->first();
    }

    protected function hasCurrentLessonProgressLoaded(): bool
    {
        if (!$this->relationLoaded('modules')) {
            return false;
        }

        return $this->modules->every(
            fn ($module) => $module->relationLoaded('lessons')
                && $module->lessons->every(
                    fn ($lesson) => array_key_exists('current_auth_progress_exists', $lesson->getAttributes())
                )
        );
    }

    public function certificates() {
        return $this->belongsToMany(Certificate::class, 'course_certificate')
            ->withPivot('id');
    }

    public function commissionMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_commission', 'course_id', 'user_id');
    }
}
