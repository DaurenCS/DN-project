<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'test_id',
        'total_questions',
        'correct_answers',
        'attempts',
        'percent',
        'status',
        'question_ids',
        'started_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'question_ids' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(TestAttemptAnswer::class);
    }

}
