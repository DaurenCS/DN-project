<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Lesson extends Model
{
    use HasTranslations, HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'course_id',
        'is_active',
        'sort_order',

    ];

    public $translatable = [
        'name',
        'description',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
