<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TestPolicy
{
    public function view(User $user, Test $test, ?Lesson $lesson = null): bool
    {
        if ($lesson) {
            if (!$user->can('access', $lesson)) {
                return false;
            }

            return $lesson->tests()->where('test_id', $test->id)->exists();
        }
        return true;
    }
}
