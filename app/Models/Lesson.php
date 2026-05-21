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
        'content',
        'module_id',
        'is_active',
        'sort_order',

    ];

    protected $casts = [
        'name' => 'array',
        'content' => 'array', // Должно быть тут
        'is_active' => 'boolean',
    ];

    public $translatable = [
        'name',
        'description',
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
    public function tests()
    {
        return $this->hasMany(Test::class)->orderBy('sort_order', 'asc');
    }

}
