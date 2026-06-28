<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCertificate extends Model
{
    protected $table = 'course_certificate';

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }
    public function userCertificates()
    {
        return $this->hasMany(UserCertificate::class);
    }

}
