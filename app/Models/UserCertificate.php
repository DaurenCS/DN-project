<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCertificate extends Model
{
    protected $fillable = [
        'user_id',
        'course_certificate_id',
        'file_path',
        'expires_at',
    ];

    public function courseCertificate() {
        return $this->belongsTo(CourseCertificate::class, 'course_certificate_id');
    }
    public function getCourseAttribute()
    {
        return $this->courseCertificate->course;
    }
    public function getTemplateAttribute()
    {
        return $this->courseCertificate->certificate;
    }
    public function isValid(): bool {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
