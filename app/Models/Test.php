<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Test extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'title',
        'description',
        'passing_score',
        'sort_order',
        'duration',
    ];

    protected $translatable = [
        'title',
        'description',
    ];

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_test')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

}
