<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'second_name',
        'birthday',
        'email',
        'password',
        'phone',
        'position',
        'department_id',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    public function courses()
    {
        return $this->belongsToMany(Course::class, 'user_course')
            ->using(UserCourse::class)
            ->withPivot(['id','start_date', 'end_date', 'progress', 'status'])
            ->withTimestamps();
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Админ имеет доступ везде
        if ($this->hasRole('admin')) {
            return true;
        }

        // Куратор имеет доступ только в свою панель
        if ($panel->getId() === 'curator' && $this->hasRole('curator')) {
            return true;
        }

        if ($panel->getId() === 'admin' && ($this->hasRole('hr') || $this->hasRole('commission'))) {
            return true;
        }

        return false;
    }

    public function getUserCourse(int $courseId): ?object
    {
        return UserCourse::query()
            ->where('user_id', $this->id)
            ->where('course_id', $courseId)
            ->first();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function commissionCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_commission', 'user_id', 'course_id');
    }
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class, 'user_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(UserCertificate::class, 'user_id');
    }



}
