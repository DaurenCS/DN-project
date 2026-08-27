<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCourse extends Pivot
{
    use softDeletes;

    protected $table = 'user_course';

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_CONTINUE    = 'continue';
    public const STATUS_COMPLETED   = 'completed';

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function completedLessons()
    {
        return $this->hasMany(UserCourseLesson::class, 'user_course_id');
    }
    public function isStarted(): bool
    {
        return $this->start_date !== null;
    }

    public function isFullyPassed(): bool
    {
        return $this->progress >= 100;
    }
    public function start(): void
    {
        $this->update([
            'start_date' => now(),
            'status'     => self::STATUS_CONTINUE,
        ]);
    }
    public function finish(): void
    {
        $this->update([
            'end_date' => now(),
            'status'   => self::STATUS_COMPLETED,
        ]);
    }

}
