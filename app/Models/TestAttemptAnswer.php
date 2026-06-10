<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAttemptAnswer extends Model
{
    protected $fillable = [
        'test_attempt_id',
        'question_id',
        'answer_id',
    ];

    public $timestamps = false;

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
