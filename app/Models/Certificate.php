<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'name',
        'template_path',
        'validity_months'
    ];

    public function course()
    {
        return $this->belongsToMany(Course::class, 'course_certificate');
    }

}
