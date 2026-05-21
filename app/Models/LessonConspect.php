<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class LessonConspect extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'lesson_id',
        'title',
        'content',
        'sort_order',
    ];

    protected $translatable = [
        'title',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
