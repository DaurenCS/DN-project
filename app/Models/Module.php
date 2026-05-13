<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Module extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'order',
        'course_id',
    ];
    public $translatable = [
        'name',
    ];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons() {
        return $this->hasMany(Lesson::class);
    }

}
