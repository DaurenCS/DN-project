<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class LessonVideo extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'lesson_id',
        'title',
        'url',
        'duration',
        'sort_order'
    ];

    public $translatable = [
        'title',
    ];


    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
