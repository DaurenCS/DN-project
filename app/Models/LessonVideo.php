<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

class LessonVideo extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, SortableTrait;

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

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];


    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
