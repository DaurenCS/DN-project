<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

class Lesson extends Model
{
    use HasTranslations, HasFactory, SoftDeletes, SortableTrait;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'content',
        'module_id',
        'is_active',
        'sort_order',

    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    public function buildSortQuery()
    {
        return static::query()->where('module_id', $this->module_id);
    }

    protected $casts = [
        'name' => 'array',
        'content' => 'array', // Должно быть тут
        'is_active' => 'boolean',
    ];

    public $translatable = [
        'name',
        'description',
        'content',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function conspects()
    {
        return $this->hasMany(LessonConspect::class)->orderBy('sort_order', 'asc');
    }
    public function videos()
    {
        return $this->hasMany(LessonVideo::class)->orderBy('sort_order', 'asc');
    }
    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class, 'lesson_test')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('lesson_test.sort_order', 'asc');
    }

    public function currentAuthProgress()
    {
        $user = Auth::guard('sanctum')->user();

        return $this->hasOne(UserCourseLesson::class, 'lesson_id')
            ->whereHas('userCourse', function ($query) use ($user) {
                $query->where('user_id', $user?->id);
            });
    }

}
